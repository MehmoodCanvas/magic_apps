<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserManagementController extends Controller
{
    public function index()
    {
        $users = User::with('profile')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('admin.users.index', compact('users'));
    }

    public function toggleSessionPermission(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $user->can_manage_sessions = !$user->can_manage_sessions;
        $user->save();

        return response()->json([
            'status' => true,
            'message' => 'Permission updated successfully.',
            'can_manage_sessions' => $user->can_manage_sessions,
        ]);
    }
}
