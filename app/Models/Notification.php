<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $fillable = [
        'type',
        'notifiable_ty',
        'notifiable_id',
        'data',
        'read_at',
    ];

    public function markAsRead()
    {
        $this->update(['read_at' => now()]);
    }
}
