<?php

namespace App\Http\Controllers\Api\V1\Client;

use App\Actions\Client\Panier\AddToPanierAction;
use App\Actions\Client\Panier\ClearPanierAction;
use App\Actions\Client\Panier\GetPanierAction;
use App\Actions\Client\Panier\RemovePanierLigneAction;
use App\Actions\Client\Panier\UpdatePanierLigneAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Client\Panier\AddToPanierRequest;
use App\Http\Requests\V1\Client\Panier\UpdatePanierLigneRequest;
use App\Http\Resources\V1\Client\PanierResource;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PanierController extends Controller
{
    public function show(Request $request, GetPanierAction $action): JsonResponse
    {
        return ApiResponse::success(new PanierResource($action->execute($request->user()->id)));
    }

    public function store(AddToPanierRequest $request, AddToPanierAction $action): JsonResponse
    {
        $panier = $action->execute(
            $request->user()->id,
            $request->integer('piece_id'),
            $request->integer('quantite'),
        );

        return ApiResponse::success(new PanierResource($panier), 'Pièce ajoutée au panier.');
    }

    public function updateLigne(UpdatePanierLigneRequest $request, int $ligne, UpdatePanierLigneAction $action): JsonResponse
    {
        $panier = $action->execute($request->user()->id, $ligne, $request->integer('quantite'));

        return ApiResponse::success(new PanierResource($panier), 'Quantité mise à jour.');
    }

    public function destroyLigne(Request $request, int $ligne, RemovePanierLigneAction $action): JsonResponse
    {
        $panier = $action->execute($request->user()->id, $ligne);

        return ApiResponse::success(new PanierResource($panier), 'Pièce retirée du panier.');
    }

    public function clear(Request $request, ClearPanierAction $action): JsonResponse
    {
        return ApiResponse::success(new PanierResource($action->execute($request->user()->id)), 'Panier vidé.');
    }
}
