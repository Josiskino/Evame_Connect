<?php

namespace App\Http\Controllers\Api\V1\Client;

use App\Actions\Client\Sav\ListerCentresAction;
use App\Actions\Client\Sav\ListerCreneauxAction;
use App\Exceptions\BusinessException;
use App\Http\Controllers\Controller;
use App\Http\Resources\V1\Client\CentreSavResource;
use App\Repositories\Contracts\CentreSavRepositoryInterface;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CentreSavController extends Controller
{
    /** Centres SAV triés par distance (Haversine). */
    public function index(Request $request, ListerCentresAction $action): JsonResponse
    {
        $lat = $request->has('lat') ? (float) $request->query('lat') : null;
        $lng = $request->has('lng') ? (float) $request->query('lng') : null;

        return ApiResponse::success(CentreSavResource::collection($action->execute($lat, $lng)));
    }

    /** Créneaux disponibles d'un centre pour une date (défaut : aujourd'hui). */
    public function creneaux(Request $request, int $centre, ListerCreneauxAction $action, CentreSavRepositoryInterface $centres): JsonResponse
    {
        $centreSav = $centres->findActive($centre)
            ?? throw new BusinessException('Centre SAV introuvable.', 404);

        $date = $request->query('date', now()->toDateString());

        return ApiResponse::success([
            'centre_sav_id' => $centreSav->id,
            'date' => $date,
            'creneaux' => $action->execute($centreSav, $date),
        ]);
    }
}
