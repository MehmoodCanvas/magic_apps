<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FollowNotification extends Notification
{
    use Queueable;

    public $sender;

    public function __construct($sender)
    {
        $this->sender = $sender;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'follow',
            'sender_id' => $this->sender->id,
            'user_id' => $this->sender->id,
            'message' => $this->sender->first_name . ' ' . $this->sender->last_name . ' started following you',
        ];
    }
}
