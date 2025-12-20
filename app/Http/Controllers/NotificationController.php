<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Friendship;
use App\Notifications\FriendRequestReceived;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $notifications = $user->notifications()
            ->latest()
            ->take(50)
            ->get()
            ->filter(function ($notification) {
                if ($notification->type === FriendRequestReceived::class) {
                    $friendshipId = $notification->data['friendship_id'] ?? null;
                    if ($friendshipId) {
                        $friendship = Friendship::find($friendshipId);
                        return $friendship && $friendship->status === 'pending';
                    }
                }
                return true;
            })
            ->values();

        return response()->json($notifications);
    }

    public function markAsRead(Request $request, string $notificationId)
    {
        $user = $request->user();

        $notification = $user->notifications()->where('id', $notificationId)->firstOrFail();
        $notification->markAsRead();

        return response()->noContent();
    }
}
