<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MessageThread extends Model
{
    use HasFactory;

    protected $fillable = ['subject'];

    public function participants() {
        return $this->belongsToMany(User::class, 'message_thread_user', 'message_thread_id', 'user_id')
            ->withPivot(['last_read_at', 'deleted_at'])
            ->withTimestamps()
            ->withTrashed();
    }

    public function messages() {
        return $this->hasMany(Message::class);
    }
}
