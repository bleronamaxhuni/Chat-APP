<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Friendship;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function search(Request $request)
    {
        $request->validate([
            'q' => 'required|string|min:1|max:255',
        ]);

        $user = $request->user();
        $query = $request->input('q');

        $users = User::where('id', '!=', $user->id)
            ->where(function ($q) use ($query) {
                $q->where('name', 'LIKE', "%{$query}%")
                    ->orWhere('email', 'LIKE', "%{$query}%");
            })
            ->limit(20)
            ->get(['id', 'name', 'email', 'profile_image', 'last_seen_at']);

        $friendships = Friendship::where(function ($q) use ($user) {
            $q->where('requester_id', $user->id)
                ->orWhere('addressee_id', $user->id);
        })->get();

        $usersWithStatus = $users->map(function ($searchedUser) use ($user, $friendships) {
            $friendship = $friendships->first(function ($f) use ($user, $searchedUser) {
                return ($f->requester_id === $user->id && $f->addressee_id === $searchedUser->id) ||
                    ($f->requester_id === $searchedUser->id && $f->addressee_id === $user->id);
            });

            $userArray = $searchedUser->toArray();

            if ($friendship) {
                $userArray['friendship_id'] = $friendship->id;
                if ($friendship->status === 'accepted') {
                    $userArray['friendship_status'] = 'friends';
                } elseif ($friendship->status === 'pending') {
                    $userArray['friendship_status'] = $friendship->requester_id === $user->id ? 'pending_outgoing' : 'pending_incoming';
                } else {
                    $userArray['friendship_status'] = null;
                }
            } else {
                $userArray['friendship_status'] = null;
                $userArray['friendship_id'] = null;
            }

            return $userArray;
        });

        return response()->json($usersWithStatus);
    }
}
