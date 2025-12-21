<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\Conversation;

Broadcast::channel('conversation.{conversationId}', function ($user, $conversationId) {
    $conversation = Conversation::with('users')->find($conversationId);

    if (!$conversation) {
        return false;
    }

    return $conversation->users->contains($user->id);
});
