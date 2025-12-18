<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\FriendshipController;
use App\Http\Controllers\Api\NotificationController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    Route::get('/friendships/suggested', [FriendshipController::class, 'suggested'])->middleware('auth');
    Route::get('/friendships/requests', [FriendshipController::class, 'requests'])->middleware('auth');
    Route::post('/friendships', [FriendshipController::class, 'store'])->middleware('auth');
    Route::post('/friendships/{friendship}/accept', [FriendshipController::class, 'accept'])->middleware('auth');
    Route::post('/friendships/{friendship}/reject', [FriendshipController::class, 'reject'])->middleware('auth');

    Route::get('/notifications', [NotificationController::class, 'index'])->middleware('auth');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->middleware('auth');
});

Route::view('/{any}', 'app')->where('any', '.*');
