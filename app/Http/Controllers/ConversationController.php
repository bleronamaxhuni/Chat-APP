<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Friendship;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ConversationController extends Controller
{
    /**
     * Get all conversations for the authenticated user
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $conversations = $user->conversations()
            ->with(['users' => function ($query) use ($user) {
                $query->where('users.id', '!=', $user->id);
            }])
            ->with(['messages' => function ($query) {
                $query->latest()->limit(1);
            }])
            ->get()
            ->map(function ($conversation) use ($user) {
                $otherUser = $conversation->users->first();
                $lastMessage = $conversation->messages->first();

                return [
                    'id' => $conversation->id,
                    'user_id' => $otherUser ? $otherUser->id : null,
                    'name' => $otherUser ? $otherUser->name : 'Unknown',
                    'email' => $otherUser ? $otherUser->email : null,
                    'profile_image' => $otherUser ? $otherUser->profile_image : null,
                    'lastMessage' => $lastMessage ? $lastMessage->message : null,
                    'lastMessageAt' => $lastMessage ? $lastMessage->created_at : null,
                    'unreadCount' => $conversation->messages()
                        ->where('sender_id', '!=', $user->id)
                        ->where('seen', false)
                        ->count(),
                ];
            });

        return response()->json($conversations);
    }

    /**
     * Get all accepted friends for the authenticated user
     */
    public function friends(Request $request)
    {
        $user = $request->user();

        $friendships = Friendship::with(['requester', 'addressee'])
            ->where(function ($query) use ($user) {
                $query->where('requester_id', $user->id)
                    ->orWhere('addressee_id', $user->id);
            })
            ->where('status', 'accepted')
            ->get();

        $friends = $friendships->map(function ($friendship) use ($user) {
            $friend = $friendship->requester_id === $user->id
                ? $friendship->addressee
                : $friendship->requester;

            return [
                'id' => $friend->id,
                'name' => $friend->name,
                'email' => $friend->email,
                'profile_image' => $friend->profile_image,
                'last_seen_at' => $friend->last_seen_at,
            ];
        });

        return response()->json($friends);
    }

    /**
     * Get or create a conversation with a specific user
     */
    public function getOrCreate(Request $request, $userId)
    {
        $user = $request->user();

        $friendship = Friendship::where(function ($query) use ($user, $userId) {
            $query->where(function ($q) use ($user, $userId) {
                $q->where('requester_id', $user->id)
                    ->where('addressee_id', $userId);
            })->orWhere(function ($q) use ($user, $userId) {
                $q->where('requester_id', $userId)
                    ->where('addressee_id', $user->id);
            });
        })
            ->where('status', 'accepted')
            ->first();

        if (!$friendship) {
            return response()->json(['message' => 'Friendship not found or not accepted'], 404);
        }

        $conversation = Conversation::whereHas('users', function ($query) use ($user) {
            $query->where('users.id', $user->id);
        })
            ->whereHas('users', function ($query) use ($userId) {
                $query->where('users.id', $userId);
            })
            ->first();

        if (!$conversation) {
            $conversation = Conversation::create();
            $conversation->users()->attach([$user->id, $userId]);
        }

        $otherUser = User::find($userId);
        $messages = $conversation->messages()
            ->with('sender')
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($message) {
                return [
                    'id' => $message->id,
                    'message' => $message->message,
                    'sender_id' => $message->sender_id,
                    'sender_name' => $message->sender->name,
                    'seen' => $message->seen,
                    'created_at' => $message->created_at,
                ];
            });

        return response()->json([
            'conversation' => [
                'id' => $conversation->id,
                'user_id' => $otherUser->id,
                'name' => $otherUser->name,
                'email' => $otherUser->email,
                'profile_image' => $otherUser->profile_image,
            ],
            'messages' => $messages,
        ]);
    }

    /**
     * Get messages for a conversation
     */
    public function messages(Request $request, Conversation $conversation)
    {
        $user = $request->user();

        $conversation->load('users');
        if (!$conversation->users->contains($user->id)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $messages = $conversation->messages()
            ->with('sender')
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($message) {
                return [
                    'id' => $message->id,
                    'message' => $message->message,
                    'sender_id' => $message->sender_id,
                    'sender_name' => $message->sender->name,
                    'seen' => $message->seen,
                    'created_at' => $message->created_at,
                ];
            });

        return response()->json($messages);
    }

    /**
     * Send a message in a conversation
     */
    public function storeMessage(Request $request, Conversation $conversation)
    {
        $user = $request->user();

        $conversation->load('users');
        if (!$conversation->users->contains($user->id)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'message' => 'required|string|max:5000',
        ]);

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $user->id,
            'message' => $request->message,
            'seen' => false,
        ]);

        $message->load('sender');

        return response()->json([
            'id' => $message->id,
            'message' => $message->message,
            'sender_id' => $message->sender_id,
            'sender_name' => $message->sender->name,
            'seen' => $message->seen,
            'created_at' => $message->created_at,
        ], 201);
    }
}
