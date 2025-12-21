<?php

namespace App\Http\Controllers\Posts;

use App\Http\Controllers\Controller;
use App\Models\Posts\Like;
use App\Models\Posts\Post;
use App\Notifications\AppNotification;
use Illuminate\Http\Request;

class LikeController extends Controller
{
    /**
     * @OA\Post(
     *     path="/posts/{post}/likes",
     *     tags={"Posts"},
     *     summary="Toggle like on post",
     *     description="Like or unlike a post. If already liked, it will unlike it.",
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
     *         description="Like toggled successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="is_liked", type="boolean", example=true, description="Whether the post is currently liked by the user"),
     *             @OA\Property(property="likes_count", type="integer", example=5, description="Total number of likes on the post")
     *         )
     *     )
     * )
     */
    public function store(Request $request, Post $post)
    {
        $user = $request->user();

        $like = Like::where('user_id', $user->id)
            ->where('post_id', $post->id)
            ->first();

        if ($like) {
            $like->delete();
            $isLiked = false;
        } else {
            $like = Like::create([
                'user_id' => $user->id,
                'post_id' => $post->id,
            ]);
            $isLiked = true;

            if ($post->user_id !== $user->id) {
                $post->load('user');
                $like->load('user');
                
                $post->user->notify(AppNotification::postLiked($like));
            }
        }

        $likesCount = Like::where('post_id', $post->id)->count();

        return response()->json([
            'is_liked' => $isLiked,
            'likes_count' => $likesCount,
        ]);
    }
}
