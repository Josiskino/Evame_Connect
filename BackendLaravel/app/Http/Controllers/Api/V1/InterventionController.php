<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Intervention\AddCommentaireAction;
use App\Actions\Intervention\CreateInterventionAction;
use App\Actions\Intervention\ListInterventionsAction;
use App\Actions\Intervention\ShowInterventionAction;
use App\Actions\Intervention\UpdateInterventionAction;
use App\DTOs\Intervention\CreateInterventionData;
use App\Events\InterventionAssigned;
use App\Events\ResourceChanged;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Intervention\StoreCommentaireRequest;
use App\Http\Requests\V1\Intervention\StoreInterventionRequest;
use App\Http\Requests\V1\Intervention\UpdateInterventionRequest;
use App\Http\Resources\V1\InterventionResource;
use App\Http\Resources\V1\UserResource;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InterventionController extends Controller
{
    public function index(Request $request, ListInterventionsAction $action): JsonResponse
    {
        // Un technicien SAV ne voit que ses propres interventions
        $technicienId = $request->user()->isSav() ? $request->user()->id : null;

        $interventions = $action->execute(
            $request->only(['date', 'statut']),
            $technicienId,
        );

        return ApiResponse::success(InterventionResource::collection($interventions));
    }

    public function store(StoreInterventionRequest $request, CreateInterventionAction $action): JsonResponse
    {
        $intervention = $action->execute(CreateInterventionData::fromArray($request->validated()));

        $label = ($intervention->client?->nom ?? 'Client').' — '.($intervention->moto?->modele ?? 'Moto');
        event(new ResourceChanged('intervention', 'created', $intervention->id, $label, $request->user()));

        // Mission assignée à un technicien dès la création -> temps réel sur son app.
        if ($intervention->technicien_id) {
            event(new InterventionAssigned($intervention, $intervention->technicien_id));
        }

        return ApiResponse::success(new InterventionResource($intervention), 'Intervention créée.', 201);
    }

    public function show(int $intervention, ShowInterventionAction $action): JsonResponse
    {
        return ApiResponse::success(new InterventionResource($action->execute($intervention)));
    }

    /** Liste des techniciens SAV (pour l'assignation côté admin). */
    public function technicians(): JsonResponse
    {
        return ApiResponse::success(
            UserResource::collection(User::role(User::ROLE_SAV)->orderBy('name')->get())
        );
    }

    public function update(UpdateInterventionRequest $request, int $intervention, UpdateInterventionAction $action): JsonResponse
    {
        $validated = $request->validated();
        $updated = $action->execute($intervention, $validated);

        $label = ($updated->client?->nom ?? 'Client').' — '.($updated->moto?->modele ?? 'Moto');
        event(new ResourceChanged('intervention', 'updated', $updated->id, $label, $request->user()));

        // (Ré)assignation explicite par l'admin -> notification temps réel au technicien.
        if (array_key_exists('technicien_id', $validated) && $updated->technicien_id) {
            event(new InterventionAssigned($updated, $updated->technicien_id));
        }

        return ApiResponse::success(new InterventionResource($updated), 'Intervention mise à jour.');
    }

    public function storeCommentaire(StoreCommentaireRequest $request, int $intervention, AddCommentaireAction $action): JsonResponse
    {
        $result = $action->execute($intervention, $request->validated()['contenu'], $request->user()->id);

        return ApiResponse::success(new InterventionResource($result), 'Commentaire ajouté.', 201);
    }
}
