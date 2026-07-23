<?php

namespace App\Http\Controllers\Api\V1\Client;

use App\Actions\Client\Commande\CreateCommandeAction;
use App\Exceptions\BusinessException;
use App\Http\Controllers\Controller;
use App\Http\Resources\V1\Client\CommandeResource;
use App\Repositories\Contracts\CommandeRepositoryInterface;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommandeClientController extends Controller
{
    /** Transforme le panier en commande (sans paiement). */
    public function store(Request $request, CreateCommandeAction $action): JsonResponse
    {
        $commande = $action->execute($request->user()->id);

        return ApiResponse::success(new CommandeResource($commande), 'Commande enregistrée.', 201);
    }

    public function index(Request $request, CommandeRepositoryInterface $commandes): JsonResponse
    {
        $items = $commandes->paginateForClient($request->user()->id, $request->integer('per_page', 15));

        return ApiResponse::success(CommandeResource::collection($items));
    }

    public function show(Request $request, int $commande, CommandeRepositoryInterface $commandes): JsonResponse
    {
        $item = $commandes->findForClient($commande, $request->user()->id)
            ?? throw new BusinessException('Commande introuvable.', 404);

        return ApiResponse::success(new CommandeResource($item));
    }
}
