<?php

namespace App\Actions\Admin\Role;

use Spatie\Permission\Models\Role;

/**
 * Cas d'usage : le Super Admin crée un rôle et lui attribue des permissions.
 */
final class CreateRoleAction
{
    /**
     * @param  array<int, string>  $permissions
     */
    public function execute(string $name, array $permissions = []): Role
    {
        $role = Role::findOrCreate($name, 'web');
        $role->syncPermissions($permissions);

        return $role->load('permissions:id,name');
    }
}
