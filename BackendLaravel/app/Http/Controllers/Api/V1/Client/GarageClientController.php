<?php

namespace App\Http\Controllers\Api\V1\Client;

use App\Actions\Client\Garage\GetGarageAction;
use App\Exceptions\BusinessException;
use App\Http\Controllers\Controller;
use App\Http\Resources\V1\Client\ContratClientResource;
use App\Http\Resources\V1\Client\DocumentResource;
use App\Http\Resources\V1\Client\GarantieResource;
use App\Http\Resources\V1\Client\MotoCatalogueResource;
use App\Http\Resources\V1\PaiementResource;
use App\Repositories\Contracts\GarageRepositoryInterface;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GarageClientController extends Controller
{
    /** Agrégat de « Mon Garage » (motos + contrats + paiements + garanties + documents). */
    public function index(Request $request, GetGarageAction $action): JsonResponse
    {
        $g = $action->execute($request->user()->id);

        return ApiResponse::success([
            'motos' => MotoCatalogueResource::collection($g['motos']),
            'contrats' => ContratClientResource::collection($g['contrats']),
            'paiements' => PaiementResource::collection($g['paiements']),
            'garanties' => GarantieResource::collection($g['garanties']),
            'documents' => DocumentResource::collection($g['documents']),
        ], 'Mon garage.');
    }

    public function showContrat(Request $request, int $contrat, GarageRepositoryInterface $garage): JsonResponse
    {
        $item = $garage->findContratForClient($contrat, $request->user()->id)
            ?? throw new BusinessException('Contrat introuvable.', 404);

        return ApiResponse::success(new ContratClientResource($item));
    }

    public function paiements(Request $request, GarageRepositoryInterface $garage): JsonResponse
    {
        return ApiResponse::success(
            PaiementResource::collection($garage->paiementsForClient($request->user()->id)),
        );
    }

    public function garanties(Request $request, GarageRepositoryInterface $garage): JsonResponse
    {
        return ApiResponse::success(
            GarantieResource::collection($garage->garantiesForClient($request->user()->id)),
        );
    }

    public function documents(Request $request, GarageRepositoryInterface $garage): JsonResponse
    {
        return ApiResponse::success(
            DocumentResource::collection($garage->documentsForClient($request->user()->id)),
        );
    }
}
