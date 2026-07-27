<?php

namespace App\Actions\Client\Sav;

use App\Models\CentreSav;
use App\Repositories\Contracts\CentreSavRepositoryInterface;
use App\Support\GeoDistance;
use Illuminate\Support\Collection;

/**
 * Cas d'usage : lister les centres SAV triés par distance (Haversine).
 */
final class ListerCentresAction
{
    // Position de repli (quartier Agoè, Lomé) quand l'app ne fournit pas de géoloc.
    private const DEFAULT_LAT = 6.2000;

    private const DEFAULT_LNG = 1.2000;

    public function __construct(
        private readonly CentreSavRepositoryInterface $centres,
    ) {}

    public function execute(?float $lat, ?float $lng): Collection
    {
        $lat ??= self::DEFAULT_LAT;
        $lng ??= self::DEFAULT_LNG;

        return $this->centres->allActive()
            ->map(function (CentreSav $centre) use ($lat, $lng) {
                $centre->distance_km = round(
                    GeoDistance::haversineKm($lat, $lng, (float) $centre->latitude, (float) $centre->longitude),
                    1,
                );

                return $centre;
            })
            ->sortBy('distance_km')
            ->values();
    }
}
