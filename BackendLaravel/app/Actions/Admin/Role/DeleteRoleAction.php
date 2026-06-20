<?php

namespace App\Actions\Admin\Role;

use App\Exceptions\BusinessException;
use App\Models\User;
use Spatie\Permission\Models\Role;

final class DeleteRoleAction
{
    public function execute(Role $role): void
    {
        if ($role->name === User::ROLE_SUPER_ADMIN) {
            throw new BusinessException('Le rôle Super Admin ne peut pas être supprimé.', 422);
        }

        $role->delete();
    }
}
