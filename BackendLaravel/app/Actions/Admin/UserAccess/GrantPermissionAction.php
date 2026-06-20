<?php

namespace App\Actions\Admin\UserAccess;

use App\Events\UserAccessUpdated;
use App\Models\User;

/**
 * Cas d'usage : accorder une permission (vue ou action) directement à un utilisateur.
 * Diffuse l'évènement temps réel pour mettre à jour ses vues immédiatement.
 */
final class GrantPermissionAction
{
    public function execute(User $user, string $permission): User
    {
        // Lève un éventuel refus puis accorde la permission directe.
        $user->allowPermission($permission);
        if (! $user->hasPermissionTo($permission)) {
            $user->givePermissionTo($permission);
        }
        $user->load(['roles:id,name', 'permissions:id,name']);

        UserAccessUpdated::dispatch($user);

        return $user;
    }
}
