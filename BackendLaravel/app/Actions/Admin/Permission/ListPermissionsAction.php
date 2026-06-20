<?php

namespace App\Actions\Admin\Permission;

use Illuminate\Support\Collection;
use Spatie\Permission\Models\Permission;

final class ListPermissionsAction
{
    public function execute(): Collection
    {
        return Permission::orderBy('name')->get(['id', 'name']);
    }
}
