<?php

namespace App\Support;

/**
 * Génère des références métier lisibles et uniques : PREFIX-ANNEE-SEQUENCE.
 * La séquence est typiquement l'id de l'enregistrement (unique garanti).
 * Ex. ReferenceGenerator::make('DEM', 1) => "DEM-2026-0001".
 */
final class ReferenceGenerator
{
    public static function make(string $prefix, int $sequence, ?int $year = null): string
    {
        $year ??= (int) now()->format('Y');

        return sprintf('%s-%d-%04d', $prefix, $year, $sequence);
    }
}
