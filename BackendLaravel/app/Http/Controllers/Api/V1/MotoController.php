<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Moto\ListMotosAction;
use App\Actions\Moto\ShowMotoAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\V1\MotoResource;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MotoController extends Controller
{
    public function index(Request $request, ListMotosAction $action): JsonResponse
    {
        $filters = $request->only(['search', 'couleur', 'disponible', 'prix_max', 'famille', 'classe_cc']);
        $motos = $action->execute($filters, $request->integer('per_page', 15));

        return ApiResponse::success(MotoResource::collection($motos));
    }

    public function show(int $moto, ShowMotoAction $action): JsonResponse
    {
        return ApiResponse::success(new MotoResource($action->execute($moto)));
    }
}
