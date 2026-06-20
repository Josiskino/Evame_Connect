<?php

namespace App\Actions\Leasing;

/**
 * Cas d'usage : calculer les montants par fréquence (journalier / hebdo / mensuel).
 */
final class SimulateLeasingAction
{
    /**
     * @return array<string, int>
     */
    public function execute(int $dureeJours, int $montantJournalier): array
    {
        $dureeJours = max(1, $dureeJours);
        $montantJournalier = max(0, $montantJournalier);
        $montantTotal = $dureeJours * $montantJournalier;
        $nombreMois = (int) ceil($dureeJours / 30);

        return [
            'duree_jours' => $dureeJours,
            'montant_journalier' => $montantJournalier,
            'montant_total' => $montantTotal,
            'montant_hebdomadaire' => $montantJournalier * 7,
            'nombre_mois' => $nombreMois,
            'montant_mensuel' => $nombreMois > 0 ? (int) round($montantTotal / $nombreMois) : 0,
        ];
    }
}
