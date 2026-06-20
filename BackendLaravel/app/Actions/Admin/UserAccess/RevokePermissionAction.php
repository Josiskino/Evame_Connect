<?php

namespace App\Actions\Admin\UserAccess;

use App\Events\UserAccessUpdated;
use App\Models\User;

/**
 * Cas d'usage : retirer une permission (vue ou action) à un utilisateur précis.
 * Diffuse l'évènement temps réel -> la vue disparaît en direct chez l'utilisateur.
 */
final class RevokePermissionAction
{
    public function execute(User $user, string $permission): User
    {
        // Retire l'éventuelle permission directe...
        if ($user->hasDirectPermission($permission)) {
            $user->revokePermissionTo($permission);
        }
        // ...et pose un refus explicite (override le rôle) pour un retrait ciblé.
        $user->denyPermission($permission);
        $user->load(['roles:id,name', 'permissions:id,name']);

        UserAccessUpdated::dispatch($user);

        return $user;
    }
}
