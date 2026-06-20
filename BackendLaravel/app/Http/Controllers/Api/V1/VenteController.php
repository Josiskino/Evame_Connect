<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Vente\CreateVenteAction;
use App\Actions\Vente\ListVentesAction;
use App\Actions\Vente\ShowVenteAction;
use App\DTOs\Vente\CreateVenteData;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Vente\StoreVenteRequest;
use App\Http\Resources\V1\VenteResource;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VenteController extends Controller
{
    public function index(Request $request, ListVentesAction $action): JsonResponse
    {
        return ApiResponse::success(
            VenteResource::collection($action->execute($request->integer('per_page', 15)))
        );
    }

    public function store(StoreVenteRequest $request, CreateVenteAction $action): JsonResponse
    {
        $vente = $action->execute(
            CreateVenteData::fromArray($request->validated(), $request->user()->id)
        );

        return ApiResponse::success(new VenteResource($vente), 'Vente enregistrée.', 201);
    }

    public function show(int $vente, ShowVenteAction $action): JsonResponse
    {
        return ApiResponse::success(new VenteResource($action->execute($vente)));
    }
}
