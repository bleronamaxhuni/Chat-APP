<?php

namespace App\Http\Controllers\Posts;

use App\Http\Controllers\Controller;
use App\Models\Posts\Like;
use App\Models\Posts\Post;
use App\Notifications\AppNotification;
use Illuminate\Http\Request;

class LikeController extends Controller
{
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
