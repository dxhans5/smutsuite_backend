<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Pivot\MessageThreadUser;

class MessageThread extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = []; // Leave empty unless thread creation is exposed via form/API

    /**
     * Get all messages that belong to this thread.
     *
     * @return HasMany
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    /**
     * Get all identities participating in this thread.
     *
     * @return BelongsToMany
     */
    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(Identity::class, 'message_thread_user')
            ->using(MessageThreadUser::class)
            ->withPivot(['last_read_at', 'deleted_at'])
            ->withTimestamps();
    }

    /**
     * Mark this thread as read by the given identity.
     * This updates the pivot table with the current timestamp.
     *
     * @param \App\Models\Identity $identity
     * @return void
     */
    public function markAsReadBy(Identity $identity): void
    {
        $this->participants()->updateExistingPivot($identity->id, [
            'last_read_at' => now(),
        ]);
    }

    /**
     * Determine if the given identity is a participant in this thread.
     *
     * @param \App\Models\Identity $identity
     * @return bool
     */
    public function hasParticipant(Identity $identity): bool
    {
        return $this->participants->contains($identity);
    }

    /**
     * Get the latest message in this thread.
     *
     * This defines a one-to-one relationship using the most recent
     * related Message, determined by the latest created_at timestamp.
     *
     * @return HasOne
     */
    public function latestMessage(): HasOne
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }
}
