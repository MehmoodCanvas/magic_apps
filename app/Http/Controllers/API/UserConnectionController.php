<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Connection;
use App\Models\UserBlock;
use App\Models\UserReport;
use App\Models\Follow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UserConnectionController extends Controller
{
    // 🔗 1. Send Connection Request
    public function sendRequest(Request $request, $id)
    {
        $sender = $request->user();
        $receiver = User::find($id);

        if (!$receiver) {
            return response()->json(['status' => false, 'message' => 'User not found'], 404);
        }

        if ($sender->id == $receiver->id) {
            return response()->json(['status' => false, 'message' => 'You cannot connect with yourself'], 400);
        }

        // Check if blocked either way
        if ($sender->hasBlocked($receiver->id) || $receiver->hasBlocked($sender->id)) {
            return response()->json(['status' => false, 'message' => 'Unable to send connection request.'], 403);
        }

        // Check if already connected
        if ($sender->isConnectedWith($receiver->id)) {
            return response()->json(['status' => false, 'message' => 'You are already connected with this user'], 400);
        }

        // Check if there is already a pending request either way
        if ($sender->hasPendingRequestWith($receiver->id)) {
            return response()->json(['status' => false, 'message' => 'A connection request is already pending'], 400);
        }

        // Create connection request
        Connection::create([
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
            'status' => 'pending',
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Connection request sent successfully',
        ]);
    }

    // 🔗 2. Accept Connection Request
    public function acceptRequest(Request $request, $id)
    {
        $receiver = $request->user();

        $connection = Connection::where('sender_id', $id)
            ->where('receiver_id', $receiver->id)
            ->where('status', 'pending')
            ->first();

        if (!$connection) {
            return response()->json(['status' => false, 'message' => 'No pending connection request found from this user'], 404);
        }

        $connection->update(['status' => 'accepted']);

        return response()->json([
            'status' => true,
            'message' => 'Connection request accepted successfully',
        ]);
    }

    // 🔗 3. Reject Connection Request
    public function rejectRequest(Request $request, $id)
    {
        $receiver = $request->user();

        $connection = Connection::where('sender_id', $id)
            ->where('receiver_id', $receiver->id)
            ->where('status', 'pending')
            ->first();

        if (!$connection) {
            return response()->json(['status' => false, 'message' => 'No pending connection request found from this user'], 404);
        }

        $connection->delete();

        return response()->json([
            'status' => true,
            'message' => 'Connection request rejected successfully',
        ]);
    }

    // 🔗 4. Unconnect (Remove Connection)
    public function unconnect(Request $request, $id)
    {
        $user = $request->user();

        $connection = Connection::where(function ($query) use ($user, $id) {
            $query->where('sender_id', $user->id)->where('receiver_id', $id);
        })->orWhere(function ($query) use ($user, $id) {
            $query->where('sender_id', $id)->where('receiver_id', $user->id);
        })->where('status', 'accepted')->first();

        if (!$connection) {
            return response()->json(['status' => false, 'message' => 'You are not connected with this user'], 404);
        }

        $connection->delete();

        return response()->json([
            'status' => true,
            'message' => 'Disconnected successfully',
        ]);
    }

    // 🔗 5. Active Connections List
    public function myConnections(Request $request)
    {
        $user = $request->user();

        $connections = $user->connections()
            ->select('users.id', 'users.first_name', 'users.last_name', 'users.email')
            ->with('profile:id,user_id,profile_picture')
            ->paginate(20);

        return response()->json([
            'status' => true,
            'data' => $connections,
        ]);
    }

    // 🔗 6. Pending Connection Requests List
    public function pendingRequests(Request $request)
    {
        $user = $request->user();

        // Get users who sent pending requests to the current user
        $requests = User::whereIn('id', function ($query) use ($user) {
                $query->select('sender_id')
                    ->from('connections')
                    ->where('receiver_id', $user->id)
                    ->where('status', 'pending');
            })
            ->select('users.id', 'users.first_name', 'users.last_name', 'users.email')
            ->with('profile:id,user_id,profile_picture')
            ->paginate(20);

        return response()->json([
            'status' => true,
            'data' => $requests,
        ]);
    }

    // 🛡️ 7. Block User
    public function blockUser(Request $request, $id)
    {
        $user = $request->user();
        $targetUser = User::find($id);

        if (!$targetUser) {
            return response()->json(['status' => false, 'message' => 'User not found'], 404);
        }

        if ($user->id == $targetUser->id) {
            return response()->json(['status' => false, 'message' => 'You cannot block yourself'], 400);
        }

        if ($user->hasBlocked($targetUser->id)) {
            return response()->json(['status' => false, 'message' => 'User is already blocked'], 400);
        }

        // Block target user
        UserBlock::create([
            'user_id' => $user->id,
            'blocked_id' => $targetUser->id,
        ]);

        // Automatically delete any pending or accepted connections between the two users
        Connection::where(function ($query) use ($user, $targetUser) {
            $query->where('sender_id', $user->id)->where('receiver_id', $targetUser->id);
        })->orWhere(function ($query) use ($user, $targetUser) {
            $query->where('sender_id', $targetUser->id)->where('receiver_id', $user->id);
        })->delete();

        return response()->json([
            'status' => true,
            'message' => 'User blocked successfully',
        ]);
    }

    // 🛡️ 8. Unblock User
    public function unblockUser(Request $request, $id)
    {
        $user = $request->user();

        $block = UserBlock::where('user_id', $user->id)
            ->where('blocked_id', $id)
            ->first();

        if (!$block) {
            return response()->json(['status' => false, 'message' => 'User is not blocked'], 404);
        }

        $block->delete();

        return response()->json([
            'status' => true,
            'message' => 'User unblocked successfully',
        ]);
    }

    // 🚩 9. Report User
    public function reportUser(Request $request, $id)
    {
        $reporter = $request->user();
        $reportedUser = User::find($id);

        if (!$reportedUser) {
            return response()->json(['status' => false, 'message' => 'User not found'], 404);
        }

        if ($reporter->id == $reportedUser->id) {
            return response()->json(['status' => false, 'message' => 'You cannot report yourself'], 400);
        }

        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors(), 'status' => false], 422);
        }

        UserReport::create([
            'reporter_id' => $reporter->id,
            'reported_id' => $reportedUser->id,
            'reason' => $request->reason,
            'description' => $request->description,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'User reported successfully',
        ]);
    }

    // ➕ 10. Follow User
    public function followUser(Request $request, $id)
    {
        $follower = $request->user();
        $following = User::find($id);

        if (!$following) {
            return response()->json(['status' => false, 'message' => 'User not found'], 404);
        }

        if ($follower->id == $following->id) {
            return response()->json(['status' => false, 'message' => 'You cannot follow yourself'], 400);
        }

        // Check if blocked either way
        if ($follower->hasBlocked($following->id) || $following->hasBlocked($follower->id)) {
            return response()->json(['status' => false, 'message' => 'Unable to follow this user.'], 403);
        }

        // Check if already following
        if ($follower->isFollowing($following->id)) {
            return response()->json([
                'status' => true,
                'message' => 'You are already following this user',
            ]);
        }

        Follow::create([
            'follower_id' => $follower->id,
            'following_id' => $following->id,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'User followed successfully',
        ]);
    }

    // ➖ 11. Unfollow User
    public function unfollowUser(Request $request, $id)
    {
        $follower = $request->user();

        $follow = Follow::where('follower_id', $follower->id)
            ->where('following_id', $id)
            ->first();

        if (!$follow) {
            return response()->json(['status' => false, 'message' => 'You are not following this user'], 404);
        }

        $follow->delete();

        return response()->json([
            'status' => true,
            'message' => 'User unfollowed successfully',
        ]);
    }

    // 👤 12. Get Target User Profile Details
    public function getProfileDetails(Request $request, $id)
    {
        $currentUser = $request->user();
        $targetUser = User::with(['profile' => function ($q) {
            $q->with([
                'country',
                'qualification',
                'employmentStatus',
                'preferredWorkStyle',
                'category',
                'subCategory'
            ]);
        }])->find($id);

        if (!$targetUser) {
            return response()->json(['status' => false, 'message' => 'User not found'], 404);
        }

        $isSelf = $currentUser->id == $targetUser->id;

        // 1. Follow status and block status
        $isFollowing = !$isSelf && $currentUser->isFollowing($targetUser->id);
        $isBlocked = !$isSelf && $currentUser->hasBlocked($targetUser->id);
        $hasBlockedMe = !$isSelf && $targetUser->hasBlocked($currentUser->id);

        // 2. Connection status
        $connectionStatus = $isSelf ? 'self' : 'none';
        
        if (!$isSelf) {
            $connection = Connection::where(function ($q) use ($currentUser, $targetUser) {
                $q->where('sender_id', $currentUser->id)->where('receiver_id', $targetUser->id);
            })->orWhere(function ($q) use ($currentUser, $targetUser) {
                $q->where('sender_id', $targetUser->id)->where('receiver_id', $currentUser->id);
            })->first();

            if ($connection) {
                if ($connection->status === 'accepted') {
                    $connectionStatus = 'connected';
                } elseif ($connection->status === 'pending') {
                    if ($connection->sender_id === $currentUser->id) {
                        $connectionStatus = 'pending_sent';
                    } else {
                        $connectionStatus = 'pending_received';
                    }
                }
            }
        }

        // 3. Counts
        $followersCount = $targetUser->followers()->count();
        $followingCount = $targetUser->followings()->count();
        $connectionsCount = $targetUser->connections()->count();

        // Achievements count: Academic trophies + achieved badges
        $trophyCount = \App\Models\AcademicPlanning::where('user_id', $targetUser->id)->where('has_trophy', true)->count();
        
        $completedSkillsCount = \App\Models\Skills::where('user_id', $targetUser->id)->where('status', 'completed')->count();
        $skillsBadgesEarned = \App\Models\Badge::where('type', 'skills')->where('required_amount', '<=', $completedSkillsCount)->count();
        
        $completedGoalsCount = \App\Models\Goal::where('user_id', $targetUser->id)->where('status', 'completed')->count();
        $goalsBadgesEarned = \App\Models\Badge::where('type', 'goals')->where('required_amount', '<=', $completedGoalsCount)->count();

        $achievementsCount = $trophyCount + $skillsBadgesEarned + $goalsBadgesEarned;

        return response()->json([
            'status' => true,
            'data' => [
                'user' => [
                    'id' => $targetUser->id,
                    'first_name' => $targetUser->first_name,
                    'last_name' => $targetUser->last_name,
                    'email' => $targetUser->email,
                    'phone' => $targetUser->phone,
                    'profile_picture' => $targetUser->profile ? $targetUser->profile->profile_picture : null,
                    'profile' => $targetUser->profile,
                ],
                'stats' => [
                    'followers_count' => $followersCount,
                    'following_count' => $followingCount,
                    'connections_count' => $connectionsCount,
                    'achievements_count' => $achievementsCount,
                ],
                'relationships' => [
                    'is_self' => $isSelf,
                    'connection_status' => $connectionStatus,
                    'is_following' => $isFollowing,
                    'is_blocked' => $isBlocked,
                    'has_blocked_me' => $hasBlockedMe,
                ]
            ]
        ]);
    }
}
