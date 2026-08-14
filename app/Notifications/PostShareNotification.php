<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PostShareNotification extends Notification
{
    use Queueable;

    public $sender;
    public $post;

    public function __construct($sender, $post)
    {
        $this->sender = $sender;
        $this->post = $post;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'post_share',
            'sender_id' => $this->sender->id,
            'user_id' => $this->sender->id,
            'post_id' => $this->post->id,
            'message' => $this->sender->first_name . ' ' . $this->sender->last_name . ' shared your post',
        ];
    }
}
