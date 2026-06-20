<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Actions\Admin\Role\CreateRoleAction;
use App\Actions\Admin\Role\DeleteRoleAction;
use App\Actions\Admin\Role\ListRolesAction;
use App\Actions\Admin\Role\SyncRolePermissionsAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\StoreRoleRequest;
use App\Http\Requests\V1\Admin\SyncRolePermissionsRequest;
use App\Http\Resources\V1\RoleResource;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index(ListRolesAction $action): JsonResponse
    {
        return ApiResponse::success(RoleResource::collection($action->execute()));
    }

    public function store(StoreRoleRequest $request, CreateRoleAction $action): JsonResponse
    {
        $data = $request->validated();
        $role = $action->execute($data['name'], $data['permissions'] ?? []);

        return ApiResponse::success(new RoleResource($role), 'Rôle créé.', 201);
    }

    public function updatePermissions(SyncRolePermissionsRequest $request, Role $role, SyncRolePermissionsAction $action): JsonResponse
    {
        $role = $action->execute($role, $request->validated()['permissions']);

        return ApiResponse::success(new RoleResource($role), 'Permissions du rôle mises à jour.');
    }

    public function destroy(Role $role, DeleteRoleAction $action): JsonResponse
    {
        $action->execute($role);

        return ApiResponse::success(null, 'Rôle supprimé.');
    }
}
