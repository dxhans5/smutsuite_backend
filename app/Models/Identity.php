<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Identity Model
 *
 * Represents a user-operable persona on the platform. A user may have multiple identities,
 * each with its own visibility, role, and profile metadata.
 *
 * @property string $id UUID primary key
 * @property string $user_id Foreign key to users table
 * @property string $alias Unique alias for the identity
 * @property string $role One of: user, creator, host, service_provider
 * @property string $visibility_level Visibility setting: public, members, hidden
 * @property string $verification_status One of: pending, verified, rejected
 * @property string|null $payout_method_id Nullable payout method (UUID)
 * @property bool $is_active Whether this identity is currently active for the user
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @property-read \App\Models\User $user
 * @property-read \App\Models\PublicProfile|null $publicProfile
 * @property-read \App\Models\PrivateProfile|null $privateProfile
 */
class Identity extends Model
{
    use HasFactory;

    /**
     * Primary key is UUID, not auto-incrementing.
     */
    public $incrementing = false;
    protected $keyType = 'string';

    /**
     * Table name if not following convention.
     */
    protected $table = 'identities';

    /**
     * Mass assignable fields.
     */
    protected $fillable = [
        'id',
        'user_id',
        'alias',
        'role',
        'visibility_level',
        'verification_status',
        'payout_method_id',
        'is_active',
    ];

    /**
     * Type casting for specific fields.
     */
    protected $casts = [
        'id' => 'string',
        'user_id' => 'string',
        'is_active' => 'boolean',
    ];

    /* -----------------------------------------------------------------
     |  Relationships
     | -----------------------------------------------------------------
     */

    /**
     * Owning user (account).
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Public-facing profile for this identity.
     */
    public function publicProfile()
    {
        return $this->hasOne(PublicProfile::class, 'identity_id');
    }

    /**
     * Internal/private profile for CRM or tools.
     */
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
    public function scopeOwnedBy($query, string $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope: only active identities.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: only verified identities.
     */
    public function scopeVerified($query)
    {
        return $query->where('verification_status', 'verified');
    }

    /**
     * Scope: discoverable public identities.
     */
    public function scopeDiscoverable($query)
    {
        return $query
            ->active()
            ->verified()
            ->whereIn('visibility_level', ['public', 'members']);
    }

    /* -----------------------------------------------------------------
     |  Accessors / Helpers
     | -----------------------------------------------------------------
     */

    /**
     * UI label fallback (alias-based).
     */
    protected function label(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->alias
        );
    }

    /**
     * Returns true if the identity is a creator.
     */
    public function isCreator(): bool
    {
        return $this->role === 'creator';
    }

    /**
     * Returns true if the identity is publicly visible.
     */
    public function isPublic(): bool
    {
        return $this->visibility_level === 'public';
    }

    /**
     * Returns true if the identity has been verified.
     */
    public function isVerified(): bool
    {
        return $this->verification_status === 'verified';
    }

    /**
     * Returns true if identity is marked active for the user.
     */
    public function isActive(): bool
    {
        return $this->is_active === true;
    }
}
