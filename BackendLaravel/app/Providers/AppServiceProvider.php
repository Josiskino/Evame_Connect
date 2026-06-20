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
        // Le Super Admin possède implicitement toutes les permissions.
        // (Le retrait ciblé d'une permission est géré au niveau de
        //  User::hasPermissionTo() — source consultée par Spatie.)
        Gate::before(function ($user, $ability) {
            return $user->hasRole(\App\Models\User::ROLE_SUPER_ADMIN) ? true : null;
        });
    }
}
