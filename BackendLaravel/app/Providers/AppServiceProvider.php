<?php

namespace App\Providers;

use App\Models\ContratLeasing;
use App\Models\User;
use App\Observers\ContratLeasingObserver;
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
            return $user->hasRole(User::ROLE_SUPER_ADMIN) ? true : null;
        });

        // Programme les rappels d'entretien à la création d'un contrat de leasing.
        ContratLeasing::observe(ContratLeasingObserver::class);
    }
}
