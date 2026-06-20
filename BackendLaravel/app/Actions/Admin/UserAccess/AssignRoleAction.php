<?php

namespace App\Actions\Admin\UserAccess;

use App\Events\UserAccessUpdated;
use App\Models\User;

/**
 * Cas d'usage : (re)définir le(s) rôle(s) d'un utilisateur.
 * Diffuse l'évènement temps réel pour mettre à jour ses vues.
 *
 * @param  array<int, string>  $roles
 */
final class AssignRoleAction
{
    /**
     * @param  array<int, string>  $roles
     */
    public function execute(User $user, array $roles): User
    {
        $user->syncRoles($roles);
        $user->load(['roles:id,name', 'permissions:id,name']);

        UserAccessUpdated::dispatch($user);

        return $user;
    }
}
