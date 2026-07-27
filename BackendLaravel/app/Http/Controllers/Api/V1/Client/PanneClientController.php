<?php

namespace App\Http\Controllers\Api\V1\Client;

use App\Actions\Client\Panne\DeclarerPanneAction;
use App\DTOs\Client\Panne\DeclarerPanneData;
use App\Exceptions\BusinessException;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Client\Panne\DeclarerPanneRequest;
use App\Http\Resources\V1\Client\InterventionClientResource;
use App\Repositories\Contracts\InterventionRepositoryInterface;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PanneClientController extends Controller
{
    /** Module 6 — Déclaration d'une panne. */
    public function store(DeclarerPanneRequest $request, DeclarerPanneAction $action): JsonResponse
    {
        $data = $request->validated();

        if ($request->hasFile('photo')) {
            $data['photo_url'] = $request->file('photo')->store('pannes', 'public');
        }

        $intervention = $action->execute(DeclarerPanneData::fromArray([
            ...$data,
            'client_id' => $request->user()->id,
        ]));

        return ApiResponse::success(
            new InterventionClientResource($intervention->load('moto')),
            'Panne déclarée. Votre dossier a été créé.',
            201,
        );
    }

    /** Module 8 — Liste des dossiers SAV du client. */
    public function index(Request $request, InterventionRepositoryInterface $interventions): JsonResponse
    {
        $items = $interventions->paginateForClient($request->user()->id, $request->integer('per_page', 15));

        return ApiResponse::success(InterventionClientResource::collection($items));
    }

    /** Module 8 — Suivi d'un dossier par numéro. */
    public function show(Request $request, string $numeroDossier, InterventionRepositoryInterface $interventions): JsonResponse
    {
        $item = $interventions->findByNumeroForClient($numeroDossier, $request->user()->id)
            ?? throw new BusinessException('Dossier introuvable.', 404);

        return ApiResponse::success(new InterventionClientResource($item));
    }
}
