<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Support\Carbon;

/**
 * Identity Model
 *
 * Represents a user-operable persona on the platform. A user may have multiple identities,
 * each with its own visibility, type, and profile metadata.
 *
 * @property string      $id                     UUID primary key
 * @property string      $user_id                Foreign key to users table
 * @property string      $alias                  Unique alias for the identity (global)
 * @property string      $type                   One of: user, creator, host, service_provider, content_provider
 * @property string|null $label                  Optional UI label; falls back to alias
 * @property string      $visibility             Visibility: public | members | hidden
 * @property string      $verification_status    One of: pending | verified | rejected
 * @property bool        $is_active              Whether this identity is currently active for the user
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property-read User $user
 * @property-read PublicProfile|null $publicProfile
 * @property-read PrivateProfile|null $privateProfile
 */
class Identity extends Model
{
    use HasFactory, HasUuids;

    /**
     * Mass assignable fields.
     */
    protected $fillable = [
        'id',
        'user_id',
        'alias',
        'type',
        'label',
        'visibility',
        'verification_status',
        'is_active',
    ];

    /**
     * Type casting for specific fields.
     */
    protected $casts = [
        'id'        => 'string',
        'user_id'   => 'string',
        'is_active' => 'boolean',
    ];

    /**
     * Canonical enums (keep in sync with DB CHECKs and validation).
     */
    public const TYPES = ['user','creator','service_provider','content_provider','host'];
    public const VISIBILITIES = ['public','members','hidden'];
    public const VERIFICATION_STATUSES = ['pending','verified','rejected'];

    /* -----------------------------------------------------------------
     |  Relationships
     | -----------------------------------------------------------------
     */

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function publicProfile()
    {
        return $this->hasOne(PublicProfile::class, 'identity_id');
    }

    public function privateProfile()
    {
        return $this->hasOne(PrivateProfile::class, 'identity_id');
    }

    /* -----------------------------------------------------------------
     |  Scopes
     | -----------------------------------------------------------------
     */

    /**
     * Scope: identities owned by a specific user.
     */
    public function scopeOwnedBy(Builder $query, string $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope: only active identities.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: only verified identities.
     */
    public function scopeVerified(Builder $query): Builder
    {
        return $query->where('verification_status', 'verified');
    }

    /**
     * Scope: discoverable public identities.
     */
    public function scopeDiscoverable(Builder $query): Builder
    {
        return $query
            ->active()
            ->verified()
            ->whereIn('visibility', ['public', 'members']);
    }

    /* -----------------------------------------------------------------
     |  Accessors / Helpers
     | -----------------------------------------------------------------
     */

    /**
     * Label accessor - returns stored label if present, otherwise falls back to alias.
     */
    protected function label(): Attribute
    {
        return Attribute::make(
            get: fn ($value, array $attributes) => $value ?? ($attributes['alias'] ?? null)
        );
    }

    /**
     * Convenience helpers.
     */
    public function isCreator(): bool
    {
        return $this->type === 'creator';
    }

    public function isPublic(): bool
    {
        return $this->visibility === 'public';
    }

    public function isVerified(): bool
    {
        return $this->verification_status === 'verified';
    }

    public function isActive(): bool
    {
        return $this->is_active === true;
    }

    /**
     * Builder for identities belonging to the given user.
     */
    public static function forUser(User $user): Builder
    {
        return static::where('user_id', $user->id);
    }
}
