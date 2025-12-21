<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Events\UserTyping;
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
     * @OA\Get(
     *     path="/conversations",
     *     tags={"Conversations"},
     *     summary="Get all conversations",
     *     description="Get all conversations for the authenticated user with last message and unread count",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of conversations",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="user_id", type="integer", example=2),
     *                 @OA\Property(property="name", type="string", example="Jane Doe"),
     *                 @OA\Property(property="email", type="string", format="email", example="jane@example.com"),
     *                 @OA\Property(property="profile_image", type="string", nullable=true),
     *                 @OA\Property(property="lastMessage", type="string", nullable=true, example="Hello!"),
     *                 @OA\Property(property="lastMessageAt", type="string", format="date-time", nullable=true),
     *                 @OA\Property(property="unreadCount", type="integer", example=3)
     *             )
     *         )
     *     )
     * )
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
     * @OA\Get(
     *     path="/conversations/friends",
     *     tags={"Conversations"},
     *     summary="Get all friends",
     *     description="Get all accepted friends for the authenticated user",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of friends",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=2),
     *                 @OA\Property(property="name", type="string", example="Jane Doe"),
     *                 @OA\Property(property="email", type="string", format="email", example="jane@example.com"),
     *                 @OA\Property(property="profile_image", type="string", nullable=true),
     *                 @OA\Property(property="last_seen_at", type="string", format="date-time", nullable=true)
     *             )
     *         )
     *     )
     * )
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
     * @OA\Get(
     *     path="/conversations/user/{userId}",
     *     tags={"Conversations"},
     *     summary="Get or create conversation with user",
     *     description="Get or create a conversation with a specific friend",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="userId",
     *         in="path",
     *         required=true,
     *         description="User ID to get/create conversation with",
     *         @OA\Schema(type="integer", example=2)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Conversation and messages",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="conversation",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="user_id", type="integer", example=2),
     *                 @OA\Property(property="name", type="string", example="Jane Doe"),
     *                 @OA\Property(property="email", type="string", format="email", example="jane@example.com"),
     *                 @OA\Property(property="profile_image", type="string", nullable=true)
     *             ),
     *             @OA\Property(
     *                 property="messages",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="message", type="string"),
     *                     @OA\Property(property="sender_id", type="integer"),
     *                     @OA\Property(property="sender_name", type="string"),
     *                     @OA\Property(property="seen", type="boolean"),
     *                     @OA\Property(property="created_at", type="string", format="date-time")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Friendship not found or not accepted")
     * )
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
     * @OA\Get(
     *     path="/conversations/{conversation}/messages",
     *     tags={"Conversations"},
     *     summary="Get conversation messages",
     *     description="Get all messages for a specific conversation",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="conversation",
     *         in="path",
     *         required=true,
     *         description="Conversation ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of messages",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="message", type="string", example="Hello!"),
     *                 @OA\Property(property="sender_id", type="integer", example=1),
     *                 @OA\Property(property="sender_name", type="string", example="John Doe"),
     *                 @OA\Property(property="seen", type="boolean", example=false),
     *                 @OA\Property(property="created_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=403, description="Unauthorized - user not part of conversation")
     * )
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
     * @OA\Post(
     *     path="/conversations/{conversation}/messages",
     *     tags={"Conversations"},
     *     summary="Send a message",
     *     description="Send a message in a conversation",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="conversation",
     *         in="path",
     *         required=true,
     *         description="Conversation ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"message"},
     *             @OA\Property(property="message", type="string", example="Hello!", description="Message content (max 5000 characters)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Message sent successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="message", type="string", example="Hello!"),
     *             @OA\Property(property="sender_id", type="integer", example=1),
     *             @OA\Property(property="sender_name", type="string", example="John Doe"),
     *             @OA\Property(property="seen", type="boolean", example=false),
     *             @OA\Property(property="created_at", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(response=403, description="Unauthorized - user not part of conversation"),
     *     @OA\Response(response=422, description="Validation error")
     * )
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

        // Broadcast the message to other users in the conversation
        broadcast(new MessageSent($message))->toOthers();

        return response()->json([
            'id' => $message->id,
            'message' => $message->message,
            'sender_id' => $message->sender_id,
            'sender_name' => $message->sender->name,
            'seen' => $message->seen,
            'created_at' => $message->created_at,
        ], 201);
    }

    /**
     * @OA\Post(
     *     path="/conversations/{conversation}/typing",
     *     tags={"Conversations"},
     *     summary="Send typing indicator",
     *     description="Broadcast typing indicator to other users in the conversation",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="conversation",
     *         in="path",
     *         required=true,
     *         description="Conversation ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"is_typing"},
     *             @OA\Property(property="is_typing", type="boolean", example=true, description="Whether user is typing")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Typing indicator sent successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(response=403, description="Unauthorized - user not part of conversation"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function typing(Request $request, Conversation $conversation)
    {
        $user = $request->user();

        $conversation->load('users');
        if (!$conversation->users->contains($user->id)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'is_typing' => 'required|boolean',
        ]);

        broadcast(new UserTyping(
            $user->id,
            $user->name,
            $conversation->id,
            $request->is_typing
        ))->toOthers();

        return response()->json(['success' => true]);
    }

    /**
     * @OA\Post(
     *     path="/conversations/{conversation}/mark-as-seen",
     *     tags={"Conversations"},
     *     summary="Mark messages as seen",
     *     description="Mark all unread messages in a conversation as seen",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="conversation",
     *         in="path",
     *         required=true,
     *         description="Conversation ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Messages marked as seen",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="marked_count", type="integer", example=5, description="Number of messages marked as seen")
     *         )
     *     ),
     *     @OA\Response(response=403, description="Unauthorized - user not part of conversation")
     * )
     */
    public function markAsSeen(Request $request, Conversation $conversation)
    {
        $user = $request->user();

        $conversation->load('users');
        if (!$conversation->users->contains($user->id)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $updated = Message::where('conversation_id', $conversation->id)
            ->where('sender_id', '!=', $user->id)
            ->where('seen', false)
            ->update(['seen' => true]);

        return response()->json([
            'success' => true,
            'marked_count' => $updated,
        ]);
    }
}
