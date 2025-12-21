<?php

namespace App\Observers;

use App\Events\NotificationCreated;
use Illuminate\Notifications\DatabaseNotification;

class NotificationObserver
{
    /**
     * Handle the notification "created" event.
     */
    public function created(DatabaseNotification $notification): void
    {
        if ($notification->type === 'App\Notifications\AppNotification') {
            $data = $notification->data;
            
            broadcast(new NotificationCreated([
                'id' => $notification->id,
                'type' => $data['type'] ?? 'unknown',
                'data' => $data,
                'read_at' => $notification->read_at?->toISOString(),
                'created_at' => $notification->created_at->toISOString(),
                'user_id' => $notification->notifiable_id,
            ]));
        }
    }
}

