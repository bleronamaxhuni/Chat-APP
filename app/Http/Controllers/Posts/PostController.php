<?php

namespace App\Http\Controllers\Posts;

use App\Http\Controllers\Controller;
use App\Models\Posts\Post;
use App\Models\Friendship;
use Illuminate\Http\Request;

class PostController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // Get all friend IDs (both requester and addressee)
        $friendIds = Friendship::where(function ($q) use ($user) {
            $q->where('requester_id', $user->id)
                ->orWhere('addressee_id', $user->id);
        })
            ->where('status', 'accepted')
            ->get()
            ->map(function ($friendship) use ($user) {
                return $friendship->requester_id === $user->id 
                    ? $friendship->addressee_id 
                    : $friendship->requester_id;
            })
            ->push($user->id); // Include own posts

        $posts = Post::with(['user', 'likes.user', 'comments.user'])
            ->whereIn('user_id', $friendIds)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($post) use ($user) {
                return [
                    'id' => $post->id,
                    'content' => $post->content,
                    'created_at' => $post->created_at,
                    'updated_at' => $post->updated_at,
                    'user' => [
                        'id' => $post->user->id,
                        'name' => $post->user->name,
                        'email' => $post->user->email,
                    ],
                    'likes_count' => $post->likes->count(),
                    'is_liked' => $post->likes->contains('user_id', $user->id),
                    'comments' => $post->comments->map(function ($comment) {
                        return [
                            'id' => $comment->id,
                            'content' => $comment->content,
                            'created_at' => $comment->created_at,
                            'user' => [
                                'id' => $comment->user->id,
                                'name' => $comment->user->name,
                            ],
                        ];
                    }),
                ];
            });

        return response()->json($posts);
    }

    public function store(Request $request)
    {
        $request->validate([
            'content' => 'required|string|max:5000',
        ]);

        $post = Post::create([
            'user_id' => $request->user()->id,
            'content' => $request->content,
        ]);

        $post->load(['user', 'likes', 'comments.user']);

        return response()->json([
            'id' => $post->id,
            'content' => $post->content,
            'created_at' => $post->created_at,
            'user' => [
                'id' => $post->user->id,
                'name' => $post->user->name,
                'email' => $post->user->email,
            ],
            'likes_count' => 0,
            'is_liked' => false,
            'comments' => [],
        ], 201);
    }

    public function update(Request $request, Post $post)
    {
        if ($post->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'content' => 'required|string|max:5000',
        ]);

        $post->update([
            'content' => $request->content,
        ]);

        $post->load(['user', 'likes.user', 'comments.user']);

        return response()->json([
            'id' => $post->id,
            'content' => $post->content,
            'created_at' => $post->created_at,
            'updated_at' => $post->updated_at,
            'user' => [
                'id' => $post->user->id,
                'name' => $post->user->name,
                'email' => $post->user->email,
            ],
            'likes_count' => $post->likes->count(),
            'is_liked' => $post->likes->contains('user_id', $request->user()->id),
            'comments' => $post->comments->map(function ($comment) {
                return [
                    'id' => $comment->id,
                    'content' => $comment->content,
                    'created_at' => $comment->created_at,
                    'user' => [
                        'id' => $comment->user->id,
                        'name' => $comment->user->name,
                    ],
                ];
            }),
        ]);
    }

    public function destroy(Request $request, Post $post)
    {
        if ($post->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $post->delete();

        return response()->json(['message' => 'Post deleted successfully']);
    }
}
