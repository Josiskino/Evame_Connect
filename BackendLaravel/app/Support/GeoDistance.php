<?php

namespace App\Support;

/**
 * Calcul de distance géographique (formule de Haversine).
 */
final class GeoDistance
{
    private const RAYON_TERRE_KM = 6371.0;

    /** Distance en kilomètres entre deux points (lat/lng en degrés). */
    public static function haversineKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return self::RAYON_TERRE_KM * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
