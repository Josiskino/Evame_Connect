<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Actions\Admin\UserAccess\AssignRoleAction;
use App\Actions\Admin\UserAccess\GrantPermissionAction;
use App\Actions\Admin\UserAccess\ListUsersAction;
use App\Actions\Admin\UserAccess\RevokePermissionAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\AssignRoleRequest;
use App\Http\Requests\V1\Admin\UpdateUserAccessRequest;
use App\Http\Resources\V1\UserResource;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserAccessController extends Controller
{
    public function index(Request $request, ListUsersAction $action): JsonResponse
    {
        $users = $action->execute($request->query('search'), $request->integer('per_page', 15));

        return ApiResponse::success(UserResource::collection($users));
    }

    /** Accorder une permission (vue/action) à un utilisateur -> broadcast temps réel. */
    public function grant(UpdateUserAccessRequest $request, User $user, GrantPermissionAction $action): JsonResponse
    {
        $user = $action->execute($user, $request->validated()['permission']);

        return ApiResponse::success(new UserResource($user), 'Permission accordée.');
    }

    /** Retirer une permission à un utilisateur -> la vue disparaît en direct. */
    public function revoke(UpdateUserAccessRequest $request, User $user, RevokePermissionAction $action): JsonResponse
    {
        $user = $action->execute($user, $request->validated()['permission']);

        return ApiResponse::success(new UserResource($user), 'Permission retirée.');
    }

    /** (Re)définir les rôles d'un utilisateur -> broadcast temps réel. */
    public function assignRoles(AssignRoleRequest $request, User $user, AssignRoleAction $action): JsonResponse
    {
        $user = $action->execute($user, $request->validated()['roles']);

        return ApiResponse::success(new UserResource($user), 'Rôles mis à jour.');
    }
}
