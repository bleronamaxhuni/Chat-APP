<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AppNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public string $type,
        public array $data
    ) {}

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     */
    public function toDatabase(object $notifiable): array
    {
        return array_merge(
            ['type' => $this->type],
            $this->data
        );
    }

    /**
     * Create a friend request received notification.
     */
    public static function friendRequestReceived($friendship): self
    {
        $friendship->loadMissing('requester');

        return new self('friend_request_received', [
            'friendship_id' => $friendship->id,
            'from_user_id' => $friendship->requester_id,
            'from_user_name' => $friendship->requester->name,
        ]);
    }

    /**
     * Create a friend request accepted notification.
     */
    public static function friendRequestAccepted($friendship): self
    {
        $friendship->loadMissing('addressee');

        return new self('friend_request_accepted', [
            'friendship_id' => $friendship->id,
            'by_user_id' => $friendship->addressee_id,
            'by_user_name' => $friendship->addressee->name,
        ]);
    }

    /**
     * Create a post liked notification.
     */
    public static function postLiked($like): self
    {
        $like->loadMissing(['user', 'post']);

        return new self('post_liked', [
            'like_id' => $like->id,
            'post_id' => $like->post_id,
            'from_user_id' => $like->user_id,
            'from_user_name' => $like->user->name ?? 'Unknown User',
        ]);
    }

    /**
     * Create a post commented notification.
     */
    public static function postCommented($comment): self
    {
        $comment->loadMissing(['user', 'post']);

        return new self('post_commented', [
            'comment_id' => $comment->id,
            'post_id' => $comment->post_id,
            'from_user_id' => $comment->user_id,
            'from_user_name' => $comment->user->name ?? 'Unknown User',
        ]);
    }
}
