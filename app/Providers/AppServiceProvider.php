<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // City-wide: executive board see everything
        Gate::define('view-all', fn ($user) => $user->isCityWide());

        // Zone directors see their own zone
        Gate::define('view-zone', function ($user, string $zone) {
            if ($user->isCityWide()) {
                return true;
            }
            return $user->isZoneLevel() && $user->zone === $zone;
        });

        // Associate directors see their own center (or above)
        Gate::define('view-center', function ($user, string $center) {
            if ($user->isCityWide() || $user->isZoneLevel()) {
                return true;
            }
            return $user->isCenterLevel() && $user->center === $center;
        });
    }
}
