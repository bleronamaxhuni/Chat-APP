<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\ConversationController;
use App\Http\Controllers\FriendshipController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Posts\PostController;
use App\Http\Controllers\Posts\LikeController;
use App\Http\Controllers\Posts\CommentController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth');
    Route::get('/me', [AuthController::class, 'me'])->middleware('auth');
    Route::post('/profile/image', [AuthController::class, 'updateProfileImage'])->middleware('auth');
    Route::put('/profile', [AuthController::class, 'updateProfile'])->middleware('auth');
    Route::put('/profile/password', [AuthController::class, 'updatePassword'])->middleware('auth');

    Route::get('/users/search', [UserController::class, 'search'])->middleware('auth');

    Route::get('/friendships/suggested', [FriendshipController::class, 'suggested'])->middleware('auth');
    Route::get('/friendships/requests', [FriendshipController::class, 'requests'])->middleware('auth');
    Route::post('/friendships', [FriendshipController::class, 'store'])->middleware('auth');
    Route::post('/friendships/{friendship}/accept', [FriendshipController::class, 'accept'])->middleware('auth');
    Route::post('/friendships/{friendship}/reject', [FriendshipController::class, 'reject'])->middleware('auth');

    Route::get('/notifications', [NotificationController::class, 'index'])->middleware('auth');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->middleware('auth');

    Route::get('/posts', [PostController::class, 'index'])->middleware('auth');
    Route::post('/posts', [PostController::class, 'store'])->middleware('auth');
    Route::put('/posts/{post}', [PostController::class, 'update'])->middleware('auth');
    Route::delete('/posts/{post}', [PostController::class, 'destroy'])->middleware('auth');

    Route::post('/posts/{post}/likes', [LikeController::class, 'store'])->middleware('auth');

    Route::post('/posts/{post}/comments', [CommentController::class, 'store'])->middleware('auth');
    Route::put('/comments/{comment}', [CommentController::class, 'update'])->middleware('auth');
    Route::delete('/comments/{comment}', [CommentController::class, 'destroy'])->middleware('auth');

    Route::get('/conversations', [ConversationController::class, 'index'])->middleware('auth');
    Route::get('/conversations/friends', [ConversationController::class, 'friends'])->middleware('auth');
    Route::get('/conversations/user/{userId}', [ConversationController::class, 'getOrCreate'])->middleware('auth');
    Route::get('/conversations/{conversation}/messages', [ConversationController::class, 'messages'])->middleware('auth');
    Route::post('/conversations/{conversation}/messages', [ConversationController::class, 'storeMessage'])->middleware('auth');
    Route::post('/conversations/{conversation}/typing', [ConversationController::class, 'typing'])->middleware('auth');
    Route::post('/conversations/{conversation}/mark-as-seen', [ConversationController::class, 'markAsSeen'])->middleware('auth');
});

Route::view('/{any}', 'app')->where('any', '.*');
