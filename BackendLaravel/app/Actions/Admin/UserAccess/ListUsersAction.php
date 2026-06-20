<?php

namespace App\Actions\Admin\UserAccess;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class ListUsersAction
{
    public function execute(?string $search = null, int $perPage = 15): LengthAwarePaginator
    {
        $query = User::with(['roles:id,name', 'permissions:id,name']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('name')->paginate($perPage);
    }
}
