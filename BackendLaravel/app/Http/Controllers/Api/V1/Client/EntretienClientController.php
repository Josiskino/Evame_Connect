<?php

namespace App\Http\Controllers\Api\V1\Client;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\Client\EntretienResource;
use App\Repositories\Contracts\EntretienRepositoryInterface;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EntretienClientController extends Controller
{
    /** Rappels d'entretien des motos du client. */
    public function index(Request $request, EntretienRepositoryInterface $entretiens): JsonResponse
    {
        return ApiResponse::success(
            EntretienResource::collection($entretiens->forClient($request->user()->id)),
        );
    }
}
