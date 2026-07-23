<?php

namespace App\Http\Controllers\Api\V1\Client;

use App\Actions\Client\Leasing\CreateDemandeLeasingAction;
use App\Actions\Client\Leasing\SimulateClientLeasingAction;
use App\DTOs\Client\Leasing\CreateDemandeLeasingData;
use App\Exceptions\BusinessException;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Client\Leasing\CreateDemandeLeasingRequest;
use App\Http\Requests\V1\Client\Leasing\SimulateLeasingRequest;
use App\Http\Resources\V1\Client\DemandeLeasingResource;
use App\Repositories\Contracts\DemandeLeasingRepositoryInterface;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeasingClientController extends Controller
{
    /** Simulation live (non persistée) à partir d'une moto. */
    public function simulate(SimulateLeasingRequest $request, SimulateClientLeasingAction $action): JsonResponse
    {
        $simulation = $action->execute($request->integer('moto_id'));

        return ApiResponse::success($simulation, 'Simulation générée.');
    }

    /** Envoi d'une demande de leasing (statut en_attente). */
    public function storeDemande(CreateDemandeLeasingRequest $request, CreateDemandeLeasingAction $action): JsonResponse
    {
        $demande = $action->execute(CreateDemandeLeasingData::fromArray([
            ...$request->validated(),
            'client_id' => $request->user()->id,
        ]));

        return ApiResponse::success(
            new DemandeLeasingResource($demande->load('moto')),
            'Votre demande de leasing a été envoyée.',
            201,
        );
    }

    /** Liste des demandes du client. */
    public function indexDemandes(Request $request, DemandeLeasingRepositoryInterface $demandes): JsonResponse
    {
        $items = $demandes->paginateForClient($request->user()->id, $request->integer('per_page', 15));

        return ApiResponse::success(DemandeLeasingResource::collection($items));
    }

    /** Détail d'une demande du client. */
    public function showDemande(Request $request, int $demande, DemandeLeasingRepositoryInterface $demandes): JsonResponse
    {
        $item = $demandes->findForClient($demande, $request->user()->id)
            ?? throw new BusinessException('Demande introuvable.', 404);

        return ApiResponse::success(new DemandeLeasingResource($item));
    }
}
