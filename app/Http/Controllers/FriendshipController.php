<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Friendship;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Notifications\FriendRequestReceived;
use App\Notifications\FriendRequestAccepted;
use App\Events\FriendRequestSent;
use App\Events\FriendRequestStatusChanged;
use App\Events\NotificationCreated;

class FriendshipController extends Controller
{
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

        $friendship->addressee->notify(new FriendRequestReceived($friendship));
        
        $notification = $friendship->addressee->notifications()
            ->where('type', FriendRequestReceived::class)
            ->whereJsonContains('data->friendship_id', $friendship->id)
            ->latest()
            ->first();
        
        if ($notification) {
            broadcast(new NotificationCreated([
                'id' => $notification->id,
                'type' => 'friend_request_received',
                'data' => [
                    'type' => 'friend_request_received',
                    'friendship_id' => $friendship->id,
                    'from_user_id' => $friendship->requester_id,
                    'from_user_name' => $friendship->requester->name,
                ],
                'read_at' => null,
                'created_at' => $notification->created_at->toISOString(),
                'user_id' => $friendship->addressee_id,
            ]));
        }

        return response()->json($friendship, 201);
    }

    public function accept(Request $request, Friendship $friendship)
    {
        $user = $request->user();

        if ($friendship->addressee_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $friendship->update(['status' => 'accepted']);

        $friendship->load(['requester', 'addressee']);

        $user->notifications()
            ->where('type', 'App\Notifications\FriendRequestReceived')
            ->whereJsonContains('data->friendship_id', $friendship->id)
            ->delete();

        broadcast(new FriendRequestStatusChanged($friendship, 'accepted'));

        $friendship->requester->notify(new FriendRequestAccepted($friendship));
        
        $notification = $friendship->requester->notifications()
            ->where('type', FriendRequestAccepted::class)
            ->whereJsonContains('data->friendship_id', $friendship->id)
            ->latest()
            ->first();
        
        if ($notification) {
            broadcast(new NotificationCreated([
                'id' => $notification->id,
                'type' => 'friend_request_accepted',
                'data' => [
                    'type' => 'friend_request_accepted',
                    'friendship_id' => $friendship->id,
                    'by_user_id' => $friendship->addressee_id,
                    'by_user_name' => $friendship->addressee->name,
                ],
                'read_at' => null,
                'created_at' => $notification->created_at->toISOString(),
                'user_id' => $friendship->requester_id,
            ]));
        }

        return response()->json($friendship);
    }

    public function reject(Request $request, Friendship $friendship)
    {
        $user = $request->user();

        if ($friendship->addressee_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $friendship->update(['status' => 'rejected']);

        $friendship->load(['requester', 'addressee']);

        $user->notifications()
            ->where('type', 'App\Notifications\FriendRequestReceived')
            ->whereJsonContains('data->friendship_id', $friendship->id)
            ->delete();

        broadcast(new FriendRequestStatusChanged($friendship, 'rejected'));

        return response()->json($friendship);
    }

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
