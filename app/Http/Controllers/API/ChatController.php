<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Conversation;
use App\Models\ConversationMember;
use App\Models\Message;
use App\Events\MessageSent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ChatController extends Controller
{
    // 💬 1. Send Message
    public function sendMessage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'conversation_id' => 'nullable|exists:conversations,id',
            'receiver_id'     => 'nullable|exists:users,id',
            'message'         => 'required|string|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors(), 'status' => false], 422);
        }

        $currentUser = $request->user();
        $messageText = $request->message;
        $conversation = null;

        // Either conversation_id or receiver_id must be provided
        if (!$request->conversation_id && !$request->receiver_id) {
            return response()->json(['status' => false, 'message' => 'Please provide conversation_id or receiver_id'], 400);
        }

        if ($request->conversation_id) {
            // Find existing conversation
            $conversation = Conversation::find($request->conversation_id);
            
            // Check if current user is member of conversation
            $isMember = $conversation->members()->where('users.id', $currentUser->id)->exists();
            if (!$isMember) {
                return response()->json(['status' => false, 'message' => 'You are not a member of this conversation'], 403);
            }

            // If direct chat, verify they are still connected
            if ($conversation->type === 'direct') {
                $otherMember = $conversation->members()->where('users.id', '!=', $currentUser->id)->first();
                if (!$otherMember || !$currentUser->isConnectedWith($otherMember->id)) {
                    return response()->json(['status' => false, 'message' => 'You can only message active connections.'], 403);
                }
            }
        } else {
            // Check if receiver_id is the user themselves
            if ($currentUser->id == $request->receiver_id) {
                return response()->json(['status' => false, 'message' => 'You cannot chat with yourself'], 400);
            }

            // Verify they are connected
            if (!$currentUser->isConnectedWith($request->receiver_id)) {
                return response()->json(['status' => false, 'message' => 'You can only message active connections.'], 403);
            }

            // Find if there is already a direct conversation between these two users
            $conversation = Conversation::where('type', 'direct')
                ->whereHas('members', function ($q) use ($currentUser) {
                    $q->where('users.id', $currentUser->id);
                })
                ->whereHas('members', function ($q) use ($request) {
                    $q->where('users.id', $request->receiver_id);
                })
                ->first();

            // If not, create a new one
            if (!$conversation) {
                DB::beginTransaction();
                try {
                    $conversation = Conversation::create([
                        'type' => 'direct',
                    ]);

                    $conversation->members()->attach([$currentUser->id, $request->receiver_id]);
                    DB::commit();
                } catch (\Exception $e) {
                    DB::rollBack();
                    return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
                }
            }
        }

        // Save the message
        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id'       => $currentUser->id,
            'message'         => $messageText,
        ]);

        // Broadcast real-time message event
        try {
            broadcast(new MessageSent($message->load('sender')))->toOthers();
        } catch (\Exception $e) {
            // Silently log/ignore broadcast errors if websockets are not fully configured yet
        }

        return response()->json([
            'status' => true,
            'message' => 'Message sent successfully',
            'data' => $message,
        ]);
    }

    // 💬 Open/Start Direct Chat (from connections list using user_id)
    public function openDirectChat(Request $request, $userId)
    {
        $currentUser = $request->user();

        // Cannot chat with yourself
        if ($currentUser->id == $userId) {
            return response()->json(['status' => false, 'message' => 'You cannot chat with yourself'], 400);
        }

        // Check if target user exists
        $targetUser = User::find($userId);
        if (!$targetUser) {
            return response()->json(['status' => false, 'message' => 'User not found'], 404);
        }

        // Verify they are connected
        if (!$currentUser->isConnectedWith($userId)) {
            return response()->json(['status' => false, 'message' => 'You can only message active connections.'], 403);
        }

        // Find existing direct conversation between these two users
        $conversation = Conversation::where('type', 'direct')
            ->whereHas('members', function ($q) use ($currentUser) {
                $q->where('users.id', $currentUser->id);
            })
            ->whereHas('members', function ($q) use ($userId) {
                $q->where('users.id', $userId);
            })
            ->first();

        // If no conversation exists, create one
        if (!$conversation) {
            DB::beginTransaction();
            try {
                $conversation = Conversation::create([
                    'type' => 'direct',
                ]);
                $conversation->members()->attach([$currentUser->id, $userId]);
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
            }
        }

        // Load conversation with messages and other member info
        $conversation->load(['members' => function ($query) use ($currentUser) {
            $query->where('users.id', '!=', $currentUser->id)
                  ->select('users.id', 'users.first_name', 'users.last_name')
                  ->with('profile:id,user_id,profile_picture');
        }]);

        $messages = $conversation->messages()
            ->with('sender:id,first_name,last_name')
            ->orderBy('created_at', 'asc')
            ->paginate(50);

        return response()->json([
            'status' => true,
            'data' => [
                'conversation' => [
                    'id'   => $conversation->id,
                    'type' => $conversation->type,
                    'other_member' => $conversation->members->first(),
                ],
                'messages' => $messages,
            ],
        ]);
    }

    // 💬 2. Get Conversation History
    public function getMessages(Request $request, $conversationId)
    {
        $currentUser = $request->user();
        $conversation = Conversation::find($conversationId);

        if (!$conversation) {
            return response()->json(['status' => false, 'message' => 'Conversation not found'], 404);
        }

        // Check membership
        $isMember = $conversation->members()->where('users.id', $currentUser->id)->exists();
        if (!$isMember) {
            return response()->json(['status' => false, 'message' => 'You are not a member of this conversation'], 403);
        }

        // If direct conversation, verify they are still connected
        if ($conversation->type === 'direct') {
            $otherMember = $conversation->members()->where('users.id', '!=', $currentUser->id)->first();
            if (!$otherMember || !$currentUser->isConnectedWith($otherMember->id)) {
                return response()->json(['status' => false, 'message' => 'Access denied. You are no longer connected with this user.'], 403);
            }
        }

        $messages = $conversation->messages()
            ->with('sender:id,first_name,last_name')
            ->orderBy('created_at', 'asc')
            ->paginate(50);

        return response()->json([
            'status' => true,
            'data' => $messages,
        ]);
    }

    // 💬 3. Get User Chat Rooms/Conversations List
    public function getChatList(Request $request)
    {
        $currentUser = $request->user();

        $conversations = $currentUser->conversations()
            ->with(['members' => function ($query) use ($currentUser) {
                $query->where('users.id', '!=', $currentUser->id)
                      ->select('users.id', 'users.first_name', 'users.last_name')
                      ->with('profile:id,user_id,profile_picture');
            }])
            ->get()
            ->map(function ($conversation) use ($currentUser) {
                // Get last message in the room
                $lastMessage = Message::where('conversation_id', $conversation->id)
                    ->orderBy('created_at', 'desc')
                    ->first();

                // For direct chats, calculate if connection is still active
                $isConnected = true;
                if ($conversation->type === 'direct') {
                    $otherMember = $conversation->members->first();
                    $isConnected = $otherMember ? $currentUser->isConnectedWith($otherMember->id) : false;
                }

                return [
                    'id'            => $conversation->id,
                    'type'          => $conversation->type,
                    'name'          => $conversation->type === 'group' ? $conversation->name : ($conversation->members->first() ? $conversation->members->first()->first_name . ' ' . $conversation->members->first()->last_name : 'User'),
                    'image'         => $conversation->type === 'group' ? $conversation->image : ($conversation->members->first() && $conversation->members->first()->profile ? $conversation->members->first()->profile->profile_picture : null),
                    'is_connected'  => $isConnected, // for direct chats
                    'last_message'  => $lastMessage ? [
                        'message'    => $lastMessage->message,
                        'sender_id'  => $lastMessage->sender_id,
                        'created_at' => $lastMessage->created_at,
                    ] : null,
                    'other_member'  => $conversation->type === 'direct' ? $conversation->members->first() : null,
                ];
            });

        return response()->json([
            'status' => true,
            'data' => $conversations,
        ]);
    }

    // 👥 4. Create Group
    public function createGroup(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'        => 'required|string|max:255',
            'image'       => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'member_ids'  => 'nullable|array',
            'member_ids.*'=> 'exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors(), 'status' => false], 422);
        }

        $currentUser = $request->user();
        $groupImagePath = null;

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $filename = time() . '_group_' . $file->getClientOriginalName();
            $file->move(public_path('group_images'), $filename);
            $groupImagePath = '/group_images/' . $filename;
        }

        DB::beginTransaction();
        try {
            $conversation = Conversation::create([
                'type'       => 'group',
                'name'       => $request->name,
                'image'      => $groupImagePath,
                'creator_id' => $currentUser->id,
            ]);

            // Group creator is automatically a member
            $members = [$currentUser->id];
            if ($request->member_ids) {
                $members = array_merge($members, $request->member_ids);
            }
            $members = array_unique($members);

            $conversation->members()->attach($members);

            DB::commit();

            return response()->json([
                'status'  => true,
                'message' => 'Group created successfully',
                'data'    => $conversation->load('members'),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // 👥 5. Update Group Info (Owner only)
    public function updateGroup(Request $request, $conversationId)
    {
        $currentUser = $request->user();
        $conversation = Conversation::where('id', $conversationId)->where('type', 'group')->first();

        if (!$conversation) {
            return response()->json(['status' => false, 'message' => 'Group not found'], 404);
        }

        // Restrict to group creator
        if ($conversation->creator_id !== $currentUser->id) {
            return response()->json(['status' => false, 'message' => 'Only the group owner/creator can update group details'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name'  => 'nullable|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors(), 'status' => false], 422);
        }

        $updateData = [];

        if ($request->has('name')) {
            $updateData['name'] = $request->name;
        }

        if ($request->hasFile('image')) {
            // Delete old image
            if ($conversation->image) {
                $oldPath = public_path($conversation->image);
                if (File::exists($oldPath)) {
                    File::delete($oldPath);
                }
            }

            $file = $request->file('image');
            $filename = time() . '_group_' . $file->getClientOriginalName();
            $file->move(public_path('group_images'), $filename);
            $updateData['image'] = '/group_images/' . $filename;
        }

        $conversation->update($updateData);

        return response()->json([
            'status'  => true,
            'message' => 'Group details updated successfully',
            'data'    => $conversation,
        ]);
    }

    // 👥 6. Add Members to Group (Owner only)
    public function addMembers(Request $request, $conversationId)
    {
        $currentUser = $request->user();
        $conversation = Conversation::where('id', $conversationId)->where('type', 'group')->first();

        if (!$conversation) {
            return response()->json(['status' => false, 'message' => 'Group not found'], 404);
        }

        if ($conversation->creator_id !== $currentUser->id) {
            return response()->json(['status' => false, 'message' => 'Only the group owner/creator can add members'], 403);
        }

        $validator = Validator::make($request->all(), [
            'member_ids'   => 'required|array',
            'member_ids.*' => 'exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors(), 'status' => false], 422);
        }

        // Filter out existing members
        $currentMemberIds = $conversation->members()->pluck('users.id')->toArray();
        $newMemberIds = array_diff($request->member_ids, $currentMemberIds);

        if (empty($newMemberIds)) {
            return response()->json(['status' => true, 'message' => 'All specified users are already group members']);
        }

        $conversation->members()->attach($newMemberIds);

        return response()->json([
            'status'  => true,
            'message' => 'Members added successfully',
            'data'    => $conversation->load('members'),
        ]);
    }

    // 👥 7. Remove Members from Group (Owner only)
    public function removeMembers(Request $request, $conversationId)
    {
        $currentUser = $request->user();
        $conversation = Conversation::where('id', $conversationId)->where('type', 'group')->first();

        if (!$conversation) {
            return response()->json(['status' => false, 'message' => 'Group not found'], 404);
        }

        if ($conversation->creator_id !== $currentUser->id) {
            return response()->json(['status' => false, 'message' => 'Only the group owner/creator can remove members'], 403);
        }

        $validator = Validator::make($request->all(), [
            'member_ids'   => 'required|array',
            'member_ids.*' => 'exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors(), 'status' => false], 422);
        }

        // Cannot remove the group owner
        if (in_array($conversation->creator_id, $request->member_ids)) {
            return response()->json(['status' => false, 'message' => 'Group creator/owner cannot be removed from the group'], 400);
        }

        $conversation->members()->detach($request->member_ids);

        return response()->json([
            'status'  => true,
            'message' => 'Members removed successfully',
            'data'    => $conversation->load('members'),
        ]);
    }
}
