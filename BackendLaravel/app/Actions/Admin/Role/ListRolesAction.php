<?php

namespace App\Actions\Admin\Role;

use Illuminate\Support\Collection;
use Spatie\Permission\Models\Role;

final class ListRolesAction
{
    public function execute(): Collection
    {
        return Role::with('permissions:id,name')->orderBy('name')->get();
    }
}
