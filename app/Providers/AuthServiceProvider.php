<?php

namespace App\Providers;

use App\Models\Identity;
use App\Policies\IdentityPolicy;
use App\Models\PublicProfile;
use App\Models\PrivateProfile;
use App\Policies\ProfilePolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Identity::class => IdentityPolicy::class,
        PublicProfile::class => ProfilePolicy::class,
        PrivateProfile::class => ProfilePolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}
