<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Leasing\CreateContratAction;
use App\Actions\Leasing\ListContratsAction;
use App\Actions\Leasing\RegisterPaiementAction;
use App\Actions\Leasing\ShowContratAction;
use App\Actions\Leasing\SimulateLeasingAction;
use App\DTOs\Leasing\CreateContratData;
use App\DTOs\Leasing\RegisterPaiementData;
use App\Events\ResourceChanged;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Leasing\SimulateLeasingRequest;
use App\Http\Requests\V1\Leasing\StoreContratLeasingRequest;
use App\Http\Requests\V1\Leasing\StorePaiementRequest;
use App\Http\Resources\V1\ContratLeasingResource;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContratLeasingController extends Controller
{
    public function index(Request $request, ListContratsAction $action): JsonResponse
    {
        $contrats = $action->execute(
            $request->boolean('en_retard'),
            $request->integer('per_page', 15)
        );

        return ApiResponse::success(ContratLeasingResource::collection($contrats));
    }

    public function store(StoreContratLeasingRequest $request, CreateContratAction $action): JsonResponse
    {
        $contrat = $action->execute(CreateContratData::fromArray($request->validated()));

        event(new ResourceChanged('leasing', 'created', $contrat->id, $contrat->client?->nom ?? 'Contrat', $request->user()));

        return ApiResponse::success(new ContratLeasingResource($contrat), 'Contrat créé.', 201);
    }

    public function show(int $leasing, ShowContratAction $action): JsonResponse
    {
        return ApiResponse::success(new ContratLeasingResource($action->execute($leasing)));
    }

    public function storePaiement(StorePaiementRequest $request, int $leasing, RegisterPaiementAction $action): JsonResponse
    {
        $contrat = $action->execute(
            $leasing,
            RegisterPaiementData::fromArray($request->validated(), $request->user()->id)
        );

        $label = ($contrat->client?->nom ?? 'Contrat').' — paiement';
        event(new ResourceChanged('paiement', 'created', $contrat->id, $label, $request->user()));

        return ApiResponse::success(new ContratLeasingResource($contrat), 'Paiement enregistré.', 201);
    }

    public function simulate(SimulateLeasingRequest $request, SimulateLeasingAction $action): JsonResponse
    {
        return ApiResponse::success($action->execute(
            $request->integer('duree_jours'),
            $request->integer('montant_journalier'),
        ));
    }
}
