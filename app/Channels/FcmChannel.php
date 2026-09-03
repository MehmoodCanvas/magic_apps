<?php

namespace App\Channels;

use App\Services\FcmService;
use Illuminate\Notifications\Notification;

class FcmChannel
{
    /**
     * Send the given notification via FCM.
     */
    public function send($notifiable, Notification $notification): void
    {
        // Check if user has FCM token
        $fcmToken = $notifiable->fcm_token ?? null;

        if (!$fcmToken) {
            return; // No token, skip FCM
        }

        // Check if notification has toFcm method
        if (!method_exists($notification, 'toFcm')) {
            return;
        }

        $fcmData = $notification->toFcm($notifiable);

        FcmService::sendNotification(
            $fcmToken,
            $fcmData['title'] ?? 'Magic App',
            $fcmData['body'] ?? '',
            $fcmData['data'] ?? []
        );
    }
}
