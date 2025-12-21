<?php

namespace App\Events;

use App\Models\Friendship;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FriendRequestStatusChanged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $friendship;
    public $status;

    /**
     * Create a new event instance.
     */
    public function __construct(Friendship $friendship, string $status)
    {
        $this->friendship = $friendship->load(['requester', 'addressee']);
        $this->status = $status;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->friendship->requester_id),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'friend.request.status.changed';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->friendship->id,
            'requester_id' => $this->friendship->requester_id,
            'addressee_id' => $this->friendship->addressee_id,
            'status' => $this->status,
            'addressee' => [
                'id' => $this->friendship->addressee->id,
                'name' => $this->friendship->addressee->name,
                'email' => $this->friendship->addressee->email,
                'profile_image' => $this->friendship->addressee->profile_image,
            ],
            'updated_at' => $this->friendship->updated_at->toISOString(),
        ];
    }
}

