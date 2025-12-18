<?php

namespace App\Notifications;

use App\Models\Friendship;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class FriendRequestReceived extends Notification
{
    use Queueable;

    public function __construct(public Friendship $friendship) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'friend_request_received',
            'friendship_id' => $this->friendship->id,
            'from_user_id' => $this->friendship->requester_id,
            'from_user_name' => $this->friendship->requester->name,
        ];
    }
}
