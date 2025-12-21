<?php

namespace App\Http\Controllers\Posts;

use App\Http\Controllers\Controller;
use App\Models\Posts\Post;
use App\Models\Friendship;
use Illuminate\Http\Request;

class PostController extends Controller
{
    /**
     * @OA\Get(
     *     path="/posts",
     *     tags={"Posts"},
     *     summary="Get posts",
     *     description="Get all posts from friends and self, ordered by creation date",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of posts",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="content", type="string", example="This is my post"),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time"),
     *                 @OA\Property(
     *                     property="user",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="name", type="string"),
     *                     @OA\Property(property="email", type="string", format="email"),
     *                     @OA\Property(property="profile_image", type="string", nullable=true)
     *                 ),
     *                 @OA\Property(property="likes_count", type="integer", example=5),
     *                 @OA\Property(property="is_liked", type="boolean", example=false),
     *                 @OA\Property(
     *                     property="comments",
     *                     type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="id", type="integer"),
     *                         @OA\Property(property="content", type="string"),
     *                         @OA\Property(property="created_at", type="string", format="date-time"),
     *                         @OA\Property(property="user", type="object")
     *                     )
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        $user = $request->user();

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
            ->push($user->id);

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
                        'profile_image' => $post->user->profile_image,
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
                                'profile_image' => $comment->user->profile_image,
                            ],
                        ];
                    }),
                ];
            });

        return response()->json($posts);
    }

    /**
     * @OA\Post(
     *     path="/posts",
     *     tags={"Posts"},
     *     summary="Create a post",
     *     description="Create a new post",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"content"},
     *             @OA\Property(property="content", type="string", example="This is my new post", description="Post content (max 5000 characters)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Post created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="content", type="string", example="This is my new post"),
     *             @OA\Property(property="created_at", type="string", format="date-time"),
     *             @OA\Property(property="user", type="object"),
     *             @OA\Property(property="likes_count", type="integer", example=0),
     *             @OA\Property(property="is_liked", type="boolean", example=false),
     *             @OA\Property(property="comments", type="array", @OA\Items())
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
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
                'profile_image' => $post->user->profile_image,
            ],
            'likes_count' => 0,
            'is_liked' => false,
            'comments' => [],
        ], 201);
    }

    /**
     * @OA\Put(
     *     path="/posts/{post}",
     *     tags={"Posts"},
     *     summary="Update a post",
     *     description="Update an existing post (only by the post owner)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="post",
     *         in="path",
     *         required=true,
     *         description="Post ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"content"},
     *             @OA\Property(property="content", type="string", example="Updated post content", description="Post content (max 5000 characters)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Post updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="content", type="string"),
     *             @OA\Property(property="created_at", type="string", format="date-time"),
     *             @OA\Property(property="updated_at", type="string", format="date-time"),
     *             @OA\Property(property="user", type="object"),
     *             @OA\Property(property="likes_count", type="integer"),
     *             @OA\Property(property="is_liked", type="boolean"),
     *             @OA\Property(property="comments", type="array", @OA\Items())
     *         )
     *     ),
     *     @OA\Response(response=403, description="Unauthorized - not the post owner"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
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
                'profile_image' => $post->user->profile_image,
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
                        'profile_image' => $comment->user->profile_image,
                    ],
                ];
            }),
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/posts/{post}",
     *     tags={"Posts"},
     *     summary="Delete a post",
     *     description="Delete a post (only by the post owner)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="post",
     *         in="path",
     *         required=true,
     *         description="Post ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Post deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Post deleted successfully")
     *         )
     *     ),
     *     @OA\Response(response=403, description="Unauthorized - not the post owner")
     * )
     */
    public function destroy(Request $request, Post $post)
    {
        if ($post->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $post->delete();

        return response()->json(['message' => 'Post deleted successfully']);
    }
}
