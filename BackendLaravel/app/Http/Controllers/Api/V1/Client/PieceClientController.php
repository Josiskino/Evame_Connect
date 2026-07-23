<?php

namespace App\Http\Controllers\Api\V1\Client;

use App\Exceptions\BusinessException;
use App\Http\Controllers\Controller;
use App\Http\Resources\V1\Client\PieceResource;
use App\Repositories\Contracts\PieceRepositoryInterface;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Catalogue des pièces détachées (lecture seule).
 */
class PieceClientController extends Controller
{
    public function index(Request $request, PieceRepositoryInterface $pieces): JsonResponse
    {
        $items = $pieces->paginate($request->query('search'), $request->integer('per_page', 15));

        return ApiResponse::success(PieceResource::collection($items));
    }

    public function show(int $piece, PieceRepositoryInterface $pieces): JsonResponse
    {
        $item = $pieces->find($piece)
            ?? throw new BusinessException('Pièce introuvable.', 404);

        return ApiResponse::success(new PieceResource($item));
    }
}
