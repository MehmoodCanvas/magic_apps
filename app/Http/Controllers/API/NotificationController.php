<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $notifications = $request->user()->notifications()->paginate(20);

        // We stored sender_id in the data array. We could fetch sender info.
        // Actually, let's load sender objects dynamically since it's cleaner.
        $notifications->getCollection()->transform(function ($notification) {
            if (isset($notification->data['sender_id'])) {
                $sender = \App\Models\User::select('id', 'first_name', 'last_name')->find($notification->data['sender_id']);
                $notification->sender = $sender;
            }
            return $notification;
        });

        return response()->json([
            'status' => true,
            'data' => $notifications
        ]);
    }

    public function markAsRead(Request $request, $id)
    {
        $notification = $request->user()->notifications()->find($id);

        if ($notification) {
            $notification->markAsRead();
            return response()->json(['status' => true, 'message' => 'Notification marked as read']);
        }

        return response()->json(['status' => false, 'message' => 'Notification not found'], 404);
    }

    public function markAllAsRead(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->json(['status' => true, 'message' => 'All notifications marked as read']);
    }

    public function unreadCount(Request $request)
    {
        $count = $request->user()->unreadNotifications()->count();

        return response()->json([
            'status' => true,
            'unread_count' => $count
        ]);
    }
}
