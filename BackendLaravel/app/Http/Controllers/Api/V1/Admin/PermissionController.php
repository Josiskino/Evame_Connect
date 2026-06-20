<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Actions\Admin\Permission\ListPermissionsAction;
use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class PermissionController extends Controller
{
    public function index(ListPermissionsAction $action): JsonResponse
    {
        return ApiResponse::success($action->execute());
    }
}
