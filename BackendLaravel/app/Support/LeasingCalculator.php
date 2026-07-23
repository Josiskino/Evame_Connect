<?php

namespace App\Support;

/**
 * Calcul de leasing côté client, à partir du prix comptant d'une moto.
 * Source unique de vérité : utilisé par le catalogue (coût mensuel indicatif)
 * et par la simulation de leasing (Module 2).
 *
 * Règles : apport 10 %, durée 180 jours, sans intérêt (coût total = prix).
 */
final class LeasingCalculator
{
    public const TAUX_APPORT = 0.10;

    public const DUREE_JOURS = 180;

    /**
     * @return array{
     *     prix: int, apport: int, montant_finance: int, duree_jours: int,
     *     cout_journalier: int, cout_hebdomadaire: int, cout_mensuel: int, cout_total: int
     * }
     */
    public static function fromPrix(int $prix, int $dureeJours = self::DUREE_JOURS, float $tauxApport = self::TAUX_APPORT): array
    {
        $dureeJours = max(1, $dureeJours);
        $apport = (int) round($prix * $tauxApport);
        $finance = $prix - $apport;
        $coutJournalier = (int) round($finance / $dureeJours);

        return [
            'prix' => $prix,
            'apport' => $apport,
            'montant_finance' => $finance,
            'duree_jours' => $dureeJours,
            'cout_journalier' => $coutJournalier,
            'cout_hebdomadaire' => $coutJournalier * 7,
            'cout_mensuel' => $coutJournalier * 30,
            'cout_total' => $prix, // pas d'intérêt ni frais
        ];
    }

    /** Coût mensuel indicatif (affiché sur la fiche/carte du catalogue). */
    public static function coutMensuelIndicatif(int $prix): int
    {
        return self::fromPrix($prix)['cout_mensuel'];
    }
}
