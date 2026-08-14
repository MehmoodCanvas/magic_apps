<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Carbon;
use App\Mail\OtpMail;

class AuthController extends Controller
{
    // 🔐 Register
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name'     => 'required|string|max:255',
            'last_name'     => 'required|string|max:255',
            'email'    => 'required|string|email|max:255|unique:users',
            'phone'     => 'max:20',
            'password' => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors(), 'status' => false], 422);
        }

        try{
            $user = User::create([
                'first_name'     => $request->first_name,
                'last_name'     => $request->last_name,
                'email'    => $request->email,
                'phone'     => $request->phone,
                'password' => Hash::make($request->password),
            ]);

            $token = $user->createToken('api_token')->plainTextToken;

            return response()->json([
                'status' => true,
                'user'  => $user,
                'token' => $token,
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // 🔐 Login
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors(), 'status' => false], 422);
        }

        try {
            $user = User::where('email', $request->email)->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                return response()->json(['status' => false, 'message' => 'Invalid credentials'], 401);
            }

            if ($user->is_active === false) {
                return response()->json(['status' => false, 'message' => 'Your account has been deactivated'], 403);
            }

            $token = $user->createToken('api_token')->plainTextToken;

            return response()->json([
                'status' => true,
                'user'  => $user,
                'token' => $token,
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }


    public function changePassword(Request $request)
    {
        $validated = Validator::make($request->all(), ['updatePassword',
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        if ($validated->fails()) {
            return response()->json(['errors' => $validated->errors(), 'status' => false], 422);
        }

        try {
            $user = $request->user();

            $user->update([
                'password' => Hash::make($request->password),
            ]);

            return response()->json(['status' => true, 'message' => 'Password changed successfully']);

        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function deactivateAccount(Request $request)
    {
        try {
            $user = $request->user();
            
            $user->update([
                'is_active' => false
            ]);

            // Revoke all tokens
            $user->tokens()->delete();

            return response()->json([
                'status' => true,
                'message' => 'Account deactivated successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // 🔐 Logout
    public function logout(Request $request)
    {
        try {

            $request->user()->currentAccessToken()->delete();
            return response()->json(['status' => true, 'message' => 'Logged out']);

        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // 👤 Authenticated user
    public function user(Request $request)
    {
        try{
            $user = $request->user();
            $profile = $user->profile ? $user->profile->load([
                'country',
                'qualification',
                'employmentStatus',
                'preferredWorkStyle',
                'category',
                'subCategory'
            ]) : null;

            // Counts
            $followersCount = $user->followers()->count();
            $followingCount = $user->followings()->count();
            $connectionsCount = $user->connections()->count();

            // Achievements count: Academic trophies + achieved badges
            $trophyCount = \App\Models\AcademicPlanning::where('user_id', $user->id)->where('has_trophy', true)->count();
            
            $completedSkillsCount = \App\Models\Skills::where('user_id', $user->id)->where('status', 'completed')->count();
            $skillsBadgesEarned = \App\Models\Badge::where('type', 'skills')->where('required_amount', '<=', $completedSkillsCount)->count();
            
            $completedGoalsCount = \App\Models\Goal::where('user_id', $user->id)->where('status', 'completed')->count();
            $goalsBadgesEarned = \App\Models\Badge::where('type', 'goals')->where('required_amount', '<=', $completedGoalsCount)->count();

            $achievementsCount = $trophyCount + $skillsBadgesEarned + $goalsBadgesEarned;

            return response()->json([
                'status' => true,
                'user' => $user,
                // 'profile' => $profile,
                'stats' => [
                    'followers_count' => $followersCount,
                    'following_count' => $followingCount,
                    'connections_count' => $connectionsCount,
                    'achievements_count' => $achievementsCount,
                ],
                'relationships' => [
                    'is_self' => true,
                    'connection_status' => 'self', // Changed to self for authenticated user
                    'is_following' => false,
                    'is_blocked' => false,
                    'has_blocked_me' => false,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

        public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
           'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors(), 'status' => false], 422);
        }

        try {
            $user = User::where('email', $request->email)->first();

            if ($user->otp_expires_at && Carbon::now()->diffInSeconds($user->otp_expires_at) > 540) {
                return response()->json(['status' => false, 'error' => ['Please wait 60 second before requesting another OTP.']]);
            }

            $code = rand(100000, 999999); // Or Str::random(6)
            $user->otp = $code;
            $user->otp_expires_at = Carbon::now()->addMinutes(10);
            $user->save();

            Mail::send('emails.otp', ['otp' => $code, 'user' => $user], function ($message) use ($user) {
                $message->to($user->email);
                $message->subject('Your OTP Code');
            });

            return response()->json([
                'otp_for_testing' => $code,
                'status' => true,
                'message' => 'OTP sent to your email.',
            ]);

        } catch (\Exception $e) {
            return response()->json(['status' => false, 'errors' => [$e->getMessage()], 'otp_for_testing' => $code ?? null], 500);
        }
    }



    public function verifyAndReset(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'otp' => 'required|string',
            'password' => 'required|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => [$validator->errors()], 'status' => false], 422);
        }

        try {
            $user = User::where('email', $request->email)->first();

            if (
                !$user ||
                $user->otp !== $request->otp ||
                Carbon::now()->gt($user->otp_expires_at)
            ) {
                return response()->json(['status' => false, 'error' => ['Invalid or expired OTP.']], 422);
            }

            $user->password = Hash::make($request->password);
            $user->otp = null;
            $user->otp_expires_at = null;
            $user->save();

            return response()->json(['status' => true, 'message' => 'Password reset successfully.']);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'errors' => [$e->getMessage()]], 500);
        }
    }
}
