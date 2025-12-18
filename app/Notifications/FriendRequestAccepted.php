<?php

namespace App\Notifications;

use App\Models\Friendship;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class FriendRequestAccepted extends Notification
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
            'type' => 'friend_request_accepted',
            'friendship_id' => $this->friendship->id,
            'by_user_id' => $this->friendship->addressee_id,
            'by_user_name' => $this->friendship->addressee->name,
        ];
    }
}
