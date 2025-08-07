<?php

namespace App\Models\Pivot;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Carbon;

/**
 * Pivot model for the many-to-many relationship between
 * MessageThread and Identity via message_thread_user table.
 *
 * Fields:
 * - id
 * - message_thread_id (foreign key)
 * - identity_id        (foreign key)
 * - last_read_at       (nullable timestamp)
 * - deleted_at         (for soft deletes)
 * - created_at / updated_at
 */
class MessageThreadUser extends Pivot
{
    use SoftDeletes;

    protected $table = 'message_thread_user';

    protected $primaryKey = 'id';

    public $incrementing = true;

    public $timestamps = true;

    protected $casts = [
        'last_read_at' => 'datetime',
    ];

    protected $fillable = [
        'message_thread_id',
        'identity_id',
        'last_read_at',
    ];

    /**
     * Accessor to check if the thread has been read recently.
     */
    protected function isRecentlyRead(): Attribute
    {
        return Attribute::get(fn () => $this->last_read_at?->gt(now()->subMinutes(10)));
    }

    /**
     * Mark the thread as read right now.
     */
    public function markAsRead(): void
    {
        $this->last_read_at = Carbon::now();
        $this->save();
    }

    /**
     * Determine if this participant has read the latest message.
     *
     * @param  \App\Models\Message  $latestMessage
     * @return bool
     */
    public function hasReadMessage(\App\Models\Message $latestMessage): bool
    {
        return $this->last_read_at && $this->last_read_at->gte($latestMessage->created_at);
    }
}
