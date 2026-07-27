<?php

namespace App\Http\Controllers\Api\V1\Client;

use App\Actions\Client\Sav\PrendreRendezVousAction;
use App\Exceptions\BusinessException;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Client\Sav\PrendreRendezVousRequest;
use App\Http\Resources\V1\Client\RendezVousResource;
use App\Repositories\Contracts\RendezVousRepositoryInterface;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RendezVousController extends Controller
{
    public function store(PrendreRendezVousRequest $request, PrendreRendezVousAction $action): JsonResponse
    {
        $rdv = $action->execute(
            $request->user()->id,
            $request->integer('centre_sav_id'),
            $request->string('creneau')->toString(),
            $request->filled('intervention_id') ? $request->integer('intervention_id') : null,
        );

        return ApiResponse::success(new RendezVousResource($rdv), 'Rendez-vous confirmé.', 201);
    }

    public function index(Request $request, RendezVousRepositoryInterface $rendezVous): JsonResponse
    {
        $items = $rendezVous->paginateForClient($request->user()->id, $request->integer('per_page', 15));

        return ApiResponse::success(RendezVousResource::collection($items));
    }

    public function show(Request $request, int $rendezVous, RendezVousRepositoryInterface $rdv): JsonResponse
    {
        $item = $rdv->findForClient($rendezVous, $request->user()->id)
            ?? throw new BusinessException('Rendez-vous introuvable.', 404);

        return ApiResponse::success(new RendezVousResource($item));
    }
}
