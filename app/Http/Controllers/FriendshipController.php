<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Friendship;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Notifications\AppNotification;
use App\Events\FriendRequestSent;
use App\Events\FriendRequestStatusChanged;

class FriendshipController extends Controller
{
    /**
     * @OA\Get(
     *     path="/friendships/suggested",
     *     tags={"Friendships"},
     *     summary="Get suggested friends",
     *     description="Get list of suggested friends (users who were active yesterday and are not already friends)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of suggested friends",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Jane Doe"),
     *                 @OA\Property(property="email", type="string", format="email", example="jane@example.com"),
     *                 @OA\Property(property="profile_image", type="string", nullable=true),
     *                 @OA\Property(property="last_seen_at", type="string", format="date-time", nullable=true)
     *             )
     *         )
     *     )
     * )
     */
    public function suggested(Request $request)
    {
        $user = $request->user();

        $friendIds = Friendship::where(function ($q) use ($user) {
            $q->where('requester_id', $user->id)
                ->orWhere('addressee_id', $user->id);
        })
            ->get(['requester_id', 'addressee_id'])
            ->flatMap(function ($f) {
                return [$f->requester_id, $f->addressee_id];
            })
            ->unique()
            ->filter(fn($id) => $id !== $user->id)
            ->values();

        $suggested = User::where('id', '!=', $user->id)
            ->whereNotIn('id', $friendIds)
            ->where('last_seen_at', '>=', Carbon::yesterday())
            ->take(20)
            ->get(['id', 'name', 'email', 'profile_image', 'last_seen_at']);


        return response()->json($suggested);
    }

    /**
     * @OA\Post(
     *     path="/friendships",
     *     tags={"Friendships"},
     *     summary="Send friend request",
     *     description="Send a friend request to another user",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"user_id"},
     *             @OA\Property(property="user_id", type="integer", example=2, description="ID of the user to send friend request to")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Friend request sent successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="requester_id", type="integer", example=1),
     *             @OA\Property(property="addressee_id", type="integer", example=2),
     *             @OA\Property(property="status", type="string", example="pending"),
     *             @OA\Property(property="created_at", type="string", format="date-time"),
     *             @OA\Property(property="updated_at", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation error or cannot add yourself as friend")
     * )
     */
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id|different:auth_id',
        ], [
            'user_id.different' => 'You cannot add yourself as a friend.',
        ]);

        $authId = $request->user()->id;
        $targetId = $request->input('user_id');

        if ($authId === (int) $targetId) {
            return response()->json(['message' => 'You cannot add yourself as a friend.'], 422);
        }

        $friendship = Friendship::firstOrCreate(
            ['requester_id' => $authId, 'addressee_id' => $targetId],
            ['status' => 'pending']
        );

        $friendship->load(['requester', 'addressee']);

        broadcast(new FriendRequestSent($friendship));

        $friendship->addressee->notify(
            AppNotification::friendRequestReceived($friendship)
        );

        return response()->json($friendship, 201);
    }

    /**
     * @OA\Post(
     *     path="/friendships/{friendship}/accept",
     *     tags={"Friendships"},
     *     summary="Accept friend request",
     *     description="Accept an incoming friend request",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="friendship",
     *         in="path",
     *         required=true,
     *         description="Friendship ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Friend request accepted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="requester_id", type="integer", example=1),
     *             @OA\Property(property="addressee_id", type="integer", example=2),
     *             @OA\Property(property="status", type="string", example="accepted"),
     *             @OA\Property(property="created_at", type="string", format="date-time"),
     *             @OA\Property(property="updated_at", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(response=403, description="Unauthorized - not the recipient of the friend request")
     * )
     */
    public function accept(Request $request, Friendship $friendship)
    {
        $user = $request->user();

        if ($friendship->addressee_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $friendship->update(['status' => 'accepted']);

        $friendship->load(['requester', 'addressee']);

        $user->notifications()
            ->where('type', 'App\Notifications\AppNotification')
            ->whereJsonContains('data->type', 'friend_request_received')
            ->whereJsonContains('data->friendship_id', $friendship->id)
            ->delete();

        broadcast(new FriendRequestStatusChanged($friendship, 'accepted'));

        $friendship->requester->notify(
            AppNotification::friendRequestAccepted($friendship)
        );

        return response()->json($friendship);
    }

    /**
     * @OA\Post(
     *     path="/friendships/{friendship}/reject",
     *     tags={"Friendships"},
     *     summary="Reject friend request",
     *     description="Reject an incoming friend request",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="friendship",
     *         in="path",
     *         required=true,
     *         description="Friendship ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Friend request rejected successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="requester_id", type="integer", example=1),
     *             @OA\Property(property="addressee_id", type="integer", example=2),
     *             @OA\Property(property="status", type="string", example="rejected"),
     *             @OA\Property(property="created_at", type="string", format="date-time"),
     *             @OA\Property(property="updated_at", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(response=403, description="Unauthorized - not the recipient of the friend request")
     * )
     */
    public function reject(Request $request, Friendship $friendship)
    {
        $user = $request->user();

        if ($friendship->addressee_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $friendship->update(['status' => 'rejected']);

        $friendship->load(['requester', 'addressee']);

        $user->notifications()
            ->where('type', 'App\Notifications\AppNotification')
            ->whereJsonContains('data->type', 'friend_request_received')
            ->whereJsonContains('data->friendship_id', $friendship->id)
            ->delete();

        broadcast(new FriendRequestStatusChanged($friendship, 'rejected'));

        return response()->json($friendship);
    }

    /**
     * @OA\Get(
     *     path="/friendships/requests",
     *     tags={"Friendships"},
     *     summary="Get friend requests",
     *     description="Get all incoming and outgoing friend requests for the authenticated user",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of friend requests",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="outgoing",
     *                 type="array",
     *                 description="Friend requests sent by the user",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="requester_id", type="integer"),
     *                     @OA\Property(property="addressee_id", type="integer"),
     *                     @OA\Property(property="status", type="string"),
     *                     @OA\Property(property="addressee", type="object")
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="incoming",
     *                 type="array",
     *                 description="Friend requests received by the user",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="requester_id", type="integer"),
     *                     @OA\Property(property="addressee_id", type="integer"),
     *                     @OA\Property(property="status", type="string"),
     *                     @OA\Property(property="requester", type="object")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function requests(Request $request)
    {
        $user = $request->user();

        $outgoing = Friendship::with('addressee')
            ->where('requester_id', $user->id)
            ->orderByDesc('created_at')
            ->get();

        $incoming = Friendship::with('requester')
            ->where('addressee_id', $user->id)
            ->orderByDesc('created_at')
            ->get();


        return response()->json([
            'outgoing' => $outgoing,
            'incoming' => $incoming,
        ]);
    }
}
