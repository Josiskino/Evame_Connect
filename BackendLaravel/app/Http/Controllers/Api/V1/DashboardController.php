<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Dashboard\GetDashboardMetricsAction;
use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function index(GetDashboardMetricsAction $action): JsonResponse
    {
        return ApiResponse::success($action->execute());
    }
}
