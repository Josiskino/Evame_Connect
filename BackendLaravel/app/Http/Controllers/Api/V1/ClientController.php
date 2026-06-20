<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Client\CreateClientAction;
use App\Actions\Client\ListClientsAction;
use App\Actions\Client\ShowClientAction;
use App\DTOs\Client\CreateClientData;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Client\StoreClientRequest;
use App\Http\Resources\V1\ClientResource;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function index(Request $request, ListClientsAction $action): JsonResponse
    {
        $clients = $action->execute($request->query('search'), $request->integer('per_page', 15));

        return ApiResponse::success(ClientResource::collection($clients));
    }

    public function store(StoreClientRequest $request, CreateClientAction $action): JsonResponse
    {
        $client = $action->execute(CreateClientData::fromArray($request->validated()));

        return ApiResponse::success(new ClientResource($client), 'Client enregistré.', 201);
    }

    public function show(int $client, ShowClientAction $action): JsonResponse
    {
        return ApiResponse::success(new ClientResource($action->execute($client)));
    }
}
