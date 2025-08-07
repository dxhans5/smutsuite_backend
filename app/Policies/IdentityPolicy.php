<?php

namespace App\Policies;

use App\Models\Identity;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Policy: Identity
 *
 * Governs access to identity (persona) records. An Identity is the public-/tool-
 * facing persona that a User operates. This policy is designed for:
 *
 * 1) Owner-based access: the authenticated user who owns the identity may manage it.
 * 2) Permission-based overrides: users with explicit permissions can act regardless of ownership.
 * 3) Admin/global override via `before()` using `$user->can('identity.admin')`.
 *
 * Suggested permission names (optional, adapt to your ACL):
 * - identity.admin              => full access to all identities (global override)
 * - identity.viewAny            => list any identities (admin/staff)
 * - identity.view               => view a specific identity (non-owner)
 * - identity.create             => create identities regardless of email verification
 * - identity.update             => update any identity (non-owner)
 * - identity.delete             => delete any identity (non-owner)
 * - identity.restore            => restore soft-deleted identities
 * - identity.forceDelete        => permanently delete identities
 * - identity.switch             => switch to an identity on behalf of a user
 * - identity.manageProfiles     => manage profiles of an identity (public/private)
 */
class IdentityPolicy
{
    use HandlesAuthorization;

    /**
     * Global override executed before any ability check.
     * If the user has the "identity.admin" permission, allow everything.
     *
     * @param User $user
     * @param  string            $ability
     * @return bool|null  true to grant, null to continue with ability methods
     */
    public function before(User $user, string $ability): ?bool
    {
        return $user->can('identity.admin') ? true : null;
    }

    /**
     * Determine whether the user can list identities.
     * Owners should be able to list their own identities; staff may have broader rights.
     *
     * @param User $user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        // Allow authenticated users to list THEIR identities; staff can view all via permission.
        return true === $user->can('identity.viewAny') || true;
    }

    /**
     * Determine whether the user can view a specific identity.
     *
     * @param User $user
     * @param Identity $identity
     * @return bool
     */
    public function view(User $user, Identity $identity): bool
    {
        return $this->owns($user, $identity) || $user->can('identity.view');
    }

    /**
     * Determine whether the user can create identities.
     * Default: require verified email for self-serve creation, OR explicit permission.
     *
     * @param User $user
     * @return bool
     */
    public function create(User $user): bool
    {
        return $user->can('identity.create') || !is_null($user->email_verified_at);
    }

    /**
     * Determine whether the user can update the identity.
     *
     * @param User $user
     * @param Identity $identity
     * @return bool
     */
    public function update(User $user, Identity $identity): bool
    {
        return $this->owns($user, $identity) || $user->can('identity.update');
    }

    /**
     * Determine whether the user can delete the identity.
     * Business-safe default: owners cannot delete the identity if it is currently active
     * on their account (avoid foot-guns). Staff with permission can override.
     *
     * @param User $user
     * @param Identity $identity
     * @return bool
     */
    public function delete(User $user, Identity $identity): bool
    {
        if ($user->can('identity.delete')) {
            return true;
        }

        if (!$this->owns($user, $identity)) {
            return false;
        }

        // Prevent deleting currently active identity for the owner
        return $user->active_identity_id !== $identity->id;
    }

    /**
     * Determine whether the user can restore a soft-deleted identity.
     *
     * @param User $user
     * @param Identity $identity
     * @return bool
     */
    public function restore(User $user, Identity $identity): bool
    {
        return $this->owns($user, $identity) || $user->can('identity.restore');
    }

    /**
     * Determine whether the user can permanently delete an identity.
     *
     * @param User $user
     * @param Identity $identity
     * @return bool
     */
    public function forceDelete(User $user, Identity $identity): bool
    {
        return $user->can('identity.forceDelete');
    }

    /**
     * Custom ability: switch the user's active identity to this one.
     * Default: owners can switch to their active&valid identities; staff may override.
     *
     * @param User $user
     * @param Identity $identity
     * @return bool
     */
    public function switch(User $user, Identity $identity): bool
    {
        if ($user->can('identity.switch')) {
            return true;
        }

        // Must own the identity and it should be in a usable state.
        $usable = strcasecmp($identity->status, 'active') === 0;
        return $this->owns($user, $identity) && $usable;
    }

    /**
     * Custom ability: manage public/private profiles for the identity.
     *
     * @param User $user
     * @param Identity $identity
     * @return bool
     */
    public function manageProfiles(User $user, Identity $identity): bool
    {
        return $this->owns($user, $identity) || $user->can('identity.manageProfiles');
    }

    /**
     * Small helper: does the given user own the identity?
     *
     * @param User $user
     * @param Identity $identity
     * @return bool
     */
    protected function owns(User $user, Identity $identity): bool
    {
        return $identity->user_id === $user->id;
    }
}
