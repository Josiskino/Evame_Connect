<?php

namespace Database\Seeders;

use App\Models\User;
use App\Support\Permissions;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // --- Permissions (vues + actions) ---------------------------------
        foreach (Permissions::all() as $name) {
            Permission::findOrCreate($name, 'web');
        }

        // Réinitialise le cache pour que les rôles « voient » les nouvelles permissions
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // --- Rôles + attribution des permissions --------------------------
        // Super Admin : aucun besoin d'attribution explicite (Gate::before).
        Role::findOrCreate(User::ROLE_SUPER_ADMIN, 'web');

        // Manager / Direction : toutes les vues métier (lecture globale).
        Role::findOrCreate(User::ROLE_MANAGER, 'web')->syncPermissions([
            Permissions::VIEW_DASHBOARD,
            Permissions::VIEW_CATALOGUE,
            Permissions::VIEW_VENTES,
            Permissions::VIEW_CLIENTS,
            Permissions::VIEW_LEASING,
            Permissions::VIEW_INTERVENTIONS,
        ]);

        // Commercial : catalogue, clients, ventes, leasing + actions associées.
        Role::findOrCreate(User::ROLE_COMMERCIAL, 'web')->syncPermissions([
            Permissions::VIEW_CATALOGUE,
            Permissions::VIEW_CLIENTS,
            Permissions::VIEW_VENTES,
            Permissions::VIEW_LEASING,
            Permissions::CLIENT_CREATE,
            Permissions::VENTE_CREATE,
            Permissions::LEASING_CREATE,
            Permissions::PAIEMENT_CREATE,
        ]);

        // SAV / technicien : interventions + actions associées.
        Role::findOrCreate(User::ROLE_SAV, 'web')->syncPermissions([
            Permissions::VIEW_INTERVENTIONS,
            Permissions::INTERVENTION_CREATE,
            Permissions::INTERVENTION_UPDATE,
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
