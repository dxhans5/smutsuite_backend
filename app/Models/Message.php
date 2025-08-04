<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Message extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'thread_id', 'sender_id', 'body',
    ];

    public function thread() {
        return $this->belongsTo(MessageThread::class, 'message_thread_id');
    }

    public function sender() {
        return $this->belongsTo(User::class, 'sender_id');
    }
}
