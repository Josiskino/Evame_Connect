<?php

namespace App\Http\Controllers\Api\V1\Client;

use App\Actions\Moto\ListMotosAction;
use App\Actions\Moto\ShowMotoAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\V1\Client\MotoCatalogueResource;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Catalogue moto côté client (lecture seule). Réutilise les Actions internes.
 */
class MotoClientController extends Controller
{
    public function index(Request $request, ListMotosAction $action): JsonResponse
    {
        $filters = $request->only(['search', 'marque', 'prix_min', 'prix_max', 'famille', 'classe_cc']);
        $motos = $action->execute($filters, $request->integer('per_page', 15));

        return ApiResponse::success(MotoCatalogueResource::collection($motos));
    }

    public function show(int $moto, ShowMotoAction $action): JsonResponse
    {
        return ApiResponse::success(new MotoCatalogueResource($action->execute($moto)));
    }
}
