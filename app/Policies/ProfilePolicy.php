<?php

namespace App\Policies;

use App\Models\User;
use App\Models\PublicProfile;
use App\Models\PrivateProfile;

class ProfilePolicy
{
    public function updatePublic(User $user, PublicProfile $profile): bool
    {
        return $profile->identity->user_id === $user->id;
    }

    public function updatePrivate(User $user, PrivateProfile $profile): bool
    {
        return $profile->identity->user_id === $user->id;
    }

    public function deletePublic(User $user, PublicProfile $profile): bool
    {
        return $profile->identity->user_id === $user->id;
    }

    public function deletePrivate(User $user, PrivateProfile $profile): bool
    {
        return $profile->identity->user_id === $user->id;
    }
}
