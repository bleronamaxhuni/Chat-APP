<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Friendship;
use Carbon\Carbon;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * @OA\Get(
     *     path="/notifications",
     *     tags={"Notifications"},
     *     summary="Get notifications",
     *     description="Get recent notifications (last 5 minutes, max 50)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of notifications",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="id", type="string", example="abc123"),
     *                 @OA\Property(property="type", type="string", example="App\\Notifications\\AppNotification"),
     *                 @OA\Property(property="notifiable_type", type="string", example="App\\Models\\User"),
     *                 @OA\Property(property="notifiable_id", type="integer", example=1),
     *                 @OA\Property(property="data", type="object"),
     *                 @OA\Property(property="read_at", type="string", format="date-time", nullable=true),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $fiveMinutesAgo = Carbon::now()->subMinutes(5);

        $notifications = $user->notifications()
            ->where('created_at', '>=', $fiveMinutesAgo)
            ->latest()
            ->take(50)
            ->get()
            ->filter(function ($notification) {
                if ($notification->data['type'] ?? null === 'friend_request_received') {
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

    /**
     * @OA\Post(
     *     path="/notifications/{notification}/read",
     *     tags={"Notifications"},
     *     summary="Mark notification as read",
     *     description="Mark a specific notification as read",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="notification",
     *         in="path",
     *         required=true,
     *         description="Notification ID",
     *         @OA\Schema(type="string", example="abc123")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Notification marked as read"
     *     ),
     *     @OA\Response(response=404, description="Notification not found")
     * )
     */
    public function markAsRead(Request $request, string $notificationId)
    {
        $user = $request->user();

        $notification = $user->notifications()->where('id', $notificationId)->firstOrFail();
        $notification->markAsRead();

        return response()->noContent();
    }
}
