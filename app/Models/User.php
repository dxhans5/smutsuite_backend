<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * Class User
 *
 * The account record that owns one or more operational identities (personas).
 * A user logs in once, then switches between identities. Public surface area is
 * identity-scoped; sensitive/account ops stay on the user.
 *
 * @property string                          $id
 * @property string|null                     $name
 * @property string                          $email
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string|null                     $password
 * @property string|null                     $active_identity_id
 * @property array|null                      $settings
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Identity> $identities
 * @property-read \App\Models\Identity|null                                           $activeIdentity
 * @property-read \Illuminate\Support\Collection                                     $all_permissions
 */
class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable, HasUuids, SoftDeletes;

    /** Primary key is a UUID */
    public $incrementing = false;
    protected $keyType   = 'string';

    /** Mass assignment */
    protected $fillable = [
        'name',
        'email',
        'password',
        'date_of_birth',
        'role',
        'active_identity_id',
        'display_name',
        'settings',
    ];

    /** Hidden for serialization */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /** Attribute casts */
    protected $casts = [
        'id'                 => 'string',
        'email_verified_at'  => 'datetime',
        'active_identity_id' => 'string',
        'settings'           => 'array',
    ];

    /* -----------------------------------------------------------------
     |  Relationships
     | -----------------------------------------------------------------
     */

    /**
     * All identities owned by this account.
     */
    public function identities()
    {
        return $this->hasMany(Identity::class);
    }

    /**
     * The currently active identity (nullable).
     */
    public function activeIdentity()
    {
        return $this->belongsTo(Identity::class, 'active_identity_id');
    }

    /**
     * Roles directly assigned to this user.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    /**
     * Permissions directly assigned to this user.
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class);
    }

    /* -----------------------------------------------------------------
     |  Derived Properties
     | -----------------------------------------------------------------
     */

    /**
     * Accessor: Combines all permissions from roles and direct assignments.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getAllPermissionsAttribute()
    {
        return $this->roles
            ->flatMap(fn ($role) => $role->permissions)
            ->merge($this->permissions)
            ->unique('id')
            ->values();
    }

    /**
     * Append a minimal "current_identity_id" virtual attribute.
     */
    protected function currentIdentityId(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->active_identity_id
        );
    }

    /* -----------------------------------------------------------------
     |  Identity Switching Logic
     | -----------------------------------------------------------------
     */

    /**
     * Ensures the user has an active identity. Falls back to the first one.
     */
    public function ensureActiveIdentity(): ?Identity
    {
        if ($this->active_identity_id && $this->relationLoaded('activeIdentity')) {
            return $this->getRelation('activeIdentity');
        }

        if ($this->active_identity_id) {
            return $this->activeIdentity()->first();
        }

        $first = $this->identities()->orderBy('created_at')->first();

        if ($first) {
            $this->forceFill(['active_identity_id' => $first->id])->saveQuietly();
        }

        return $first;
    }

    /**
     * Switch to a given identity owned by this user.
     */
    public function switchToIdentity($identity): bool
    {
        $target = $identity instanceof Identity
            ? $identity
            : $this->identities()->find($identity);

        if (! $target) {
            return false;
        }

        if (class_exists(\App\Models\IdentitySwitchLog::class)) {
            \App\Models\IdentitySwitchLog::create([
                'user_id'     => $this->id,
                'from_id'     => $this->active_identity_id,
                'to_id'       => $target->id,
                'switched_at' => now(),
                'context'     => 'manual',
            ]);
        }

        $this->forceFill(['active_identity_id' => $target->id])->save();

        $this->setRelation('activeIdentity', $target);

        return true;
    }

    /**
     * Returns currently active identity or resolves one lazily.
     */
    public function currentIdentity(): ?Identity
    {
        return $this->activeIdentity ?? $this->ensureActiveIdentity();
    }
}
