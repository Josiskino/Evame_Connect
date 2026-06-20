<?php

namespace App\Actions\Admin\Role;

use Spatie\Permission\Models\Role;

/**
 * Cas d'usage : redéfinir les permissions d'un rôle.
 */
final class SyncRolePermissionsAction
{
    /**
     * @param  array<int, string>  $permissions
     */
    public function execute(Role $role, array $permissions): Role
    {
        $role->syncPermissions($permissions);

        return $role->load('permissions:id,name');
    }
}
