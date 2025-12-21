<?php

namespace App\Http\Controllers\Posts;

use App\Http\Controllers\Controller;
use App\Models\Posts\Comment;
use App\Models\Posts\Post;
use App\Notifications\AppNotification;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    /**
     * @OA\Post(
     *     path="/posts/{post}/comments",
     *     tags={"Posts"},
     *     summary="Create a comment",
     *     description="Add a comment to a post",
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
     *             @OA\Property(property="content", type="string", example="Great post!", description="Comment content (max 1000 characters)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Comment created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="content", type="string", example="Great post!"),
     *             @OA\Property(property="created_at", type="string", format="date-time"),
     *             @OA\Property(
     *                 property="user",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="profile_image", type="string", nullable=true)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(Request $request, Post $post)
    {
        $request->validate([
            'content' => 'required|string|max:1000',
        ]);

        $user = $request->user();

        $comment = Comment::create([
            'user_id' => $user->id,
            'post_id' => $post->id,
            'content' => $request->content,
        ]);

        $comment->load('user');
        
        if ($post->user_id !== $user->id) {
            $post->load('user');
            
            $post->user->notify(AppNotification::postCommented($comment));
        }

        return response()->json([
            'id' => $comment->id,
            'content' => $comment->content,
            'created_at' => $comment->created_at,
            'user' => [
                'id' => $comment->user->id,
                'name' => $comment->user->name,
                'profile_image' => $comment->user->profile_image,
            ],
        ], 201);
    }

    /**
     * @OA\Put(
     *     path="/comments/{comment}",
     *     tags={"Posts"},
     *     summary="Update a comment",
     *     description="Update an existing comment (only by the comment owner)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="comment",
     *         in="path",
     *         required=true,
     *         description="Comment ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"content"},
     *             @OA\Property(property="content", type="string", example="Updated comment", description="Comment content (max 1000 characters)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Comment updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="content", type="string"),
     *             @OA\Property(property="created_at", type="string", format="date-time"),
     *             @OA\Property(property="user", type="object")
     *         )
     *     ),
     *     @OA\Response(response=403, description="Unauthorized - not the comment owner"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function update(Request $request, Comment $comment)
    {
        if ($comment->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'content' => 'required|string|max:1000',
        ]);

        $comment->update([
            'content' => $request->content,
        ]);

        $comment->load('user');

        return response()->json([
            'id' => $comment->id,
            'content' => $comment->content,
            'created_at' => $comment->created_at,
            'user' => [
                'id' => $comment->user->id,
                'name' => $comment->user->name,
                'profile_image' => $comment->user->profile_image,
            ],
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/comments/{comment}",
     *     tags={"Posts"},
     *     summary="Delete a comment",
     *     description="Delete a comment (only by the comment owner)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="comment",
     *         in="path",
     *         required=true,
     *         description="Comment ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Comment deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Comment deleted successfully")
     *         )
     *     ),
     *     @OA\Response(response=403, description="Unauthorized - not the comment owner")
     * )
     */
    public function destroy(Request $request, Comment $comment)
    {
        if ($comment->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $comment->delete();

        return response()->json(['message' => 'Comment deleted successfully']);
    }
}
