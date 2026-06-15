<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DirectChat extends Model
{
    protected $table = 'direct_chats';

    protected $fillable = [
        'sender_id',
        'receiver_id',
        'message',
        'file_path',
        'file_type',
        'reply_to_id',
    ];

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    public function replyTo()
    {
        return $this->belongsTo(DirectChat::class, 'reply_to_id');
    }
}
