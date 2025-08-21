<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\PrivateProfile
 *
 * Purpose
 * -------
 * Stores **non-public** profile data for a single Identity (not User).
 * Each Identity can have at most one PrivateProfile (DB enforces unique(identity_id)).
 *
 * Notes
 * -----
 * - Keep PII here; public-facing fields belong on PublicProfile.
 * - Consider using Laravel's encrypted casts for sensitive arrays if needed.
 * - Access the owning User via $profile->identity->user, or the provided read-only accessor $profile->user.
 */
class PrivateProfile extends Model
{
    use HasFactory;

    /**
     * Mass-assignable attributes.
     */
    protected $fillable = [
        'identity_id',
        // domain fields
        'notes',
        'journal',
        'favorite_kinks',
        'mood',
        'emotional_state',
        'timezone',
        'custom_fields',
    ];

    /**
     * Attribute casting.
     * Arrays default to empty arrays to avoid null checks downstream.
     * (If you want encryption, switch 'array' -> 'encrypted:array'.)
     */
    protected $casts = [
        'notes'           => 'array',
        'journal'         => 'array',
        'favorite_kinks'  => 'array',
        'custom_fields'   => 'array',
        'mood'            => 'string',
        'emotional_state' => 'string',
        'timezone'        => 'string',
    ];

    /**
     * Sensible defaults: treat missing JSON as empty arrays.
     */
    protected $attributes = [
        'notes'          => '[]',
        'journal'        => '[]',
        'favorite_kinks' => '[]',
        'custom_fields'  => '[]',
    ];

    /**
     * Relationship: owning Identity.
     */
    public function identity(): BelongsTo
    {
        return $this->belongsTo(Identity::class, 'identity_id');
    }

    /**
     * Convenience (read-only) accessor to the owning User via Identity.
     * This is NOT an Eloquent relation (so it can't be eager-loaded directly).
     * Prefer: PrivateProfile::with('identity.user') when you need eager loading.
     */
    public function getUserAttribute(): ?User
    {
        // @phpstan-ignore-next-line (runtime-only convenience)
        return $this->relationLoaded('identity')
            ? optional($this->identity)->user
            : optional($this->identity()->first())->user;
    }

    /**
     * Scope: filter profiles by Identity ID.
     */
    public function scopeForIdentity($query, string $identityId)
    {
        return $query->where('identity_id', $identityId);
    }
}
