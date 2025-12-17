<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConversationUser extends Model
{
    protected $fillable = [
        'conversation_id',
        'user_id',
    ];

    public function conversations()
    {
        return $this->belongsToMany(Conversation::class, 'conversation_user');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'conversation_user');
    }
}
