<?php

namespace App\Providers;

use Illuminate\Http\Request;
use App\Models\Identity;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Request::macro('currentIdentity', function (): ?Identity {
            /** @var \Illuminate\Http\Request $this */
            $user = $this->user();
            if (!$user || !$user->active_identity_id) return null;
            static $cache = [];
            if (!array_key_exists($user->active_identity_id, $cache)) {
                $cache[$user->active_identity_id] = Identity::find($user->active_identity_id);
            }
            return $cache[$user->active_identity_id];
        });
    }
}
