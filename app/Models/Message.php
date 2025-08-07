<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Message extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'message_thread_id',
        'sender_identity_id',
        'body',
    ];

    public function thread()
    {
        return $this->belongsTo(MessageThread::class, 'message_thread_id');
    }

    public function senderIdentity()
    {
        return $this->belongsTo(Identity::class, 'sender_identity_id');
    }

    public function senderUser()
    {
        return $this->senderIdentity->user();
    }
}
