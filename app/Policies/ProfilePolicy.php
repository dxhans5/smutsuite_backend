<?php

namespace App\Policies;

use App\Models\User;
use App\Models\PublicProfile;
use App\Models\PrivateProfile;

class ProfilePolicy
{
    /**
     * Determine if the authenticated user can update a public profile.
     *
     * @param  User           $user
     * @param  PublicProfile  $profile
     * @return bool
     */
    public function updatePublic(User $user, PublicProfile $profile): bool
    {
        return $this->ownsIdentity($user, $profile);
    }

    /**
     * Determine if the authenticated user can update a private profile.
     *
     * @param  User            $user
     * @param  PrivateProfile  $profile
     * @return bool
     */
    public function updatePrivate(User $user, PrivateProfile $profile): bool
    {
        return $this->ownsIdentity($user, $profile);
    }

    /**
     * Determine if the authenticated user can delete a public profile.
     *
     * @param  User           $user
     * @param  PublicProfile  $profile
     * @return bool
     */
    public function deletePublic(User $user, PublicProfile $profile): bool
    {
        return $this->ownsIdentity($user, $profile);
    }

    /**
     * Determine if the authenticated user can delete a private profile.
     *
     * @param  User            $user
     * @param  PrivateProfile  $profile
     * @return bool
     */
    public function deletePrivate(User $user, PrivateProfile $profile): bool
    {
        return $this->ownsIdentity($user, $profile);
    }

    /**
     * Check if the given profile's identity belongs to the user.
     *
     * @param  User  $user
     * @param  object  $profile
     * @return bool
     */
    private function ownsIdentity(User $user, object $profile): bool
    {
        return optional($profile->identity)->user_id === $user->id;
    }
}
