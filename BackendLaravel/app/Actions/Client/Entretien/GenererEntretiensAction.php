<?php

namespace App\Actions\Client\Entretien;

use App\Models\ContratLeasing;
use App\Models\Entretien;
use Illuminate\Support\Carbon;

/**
 * Cas d'usage : programmer les rappels d'entretien d'un contrat.
 * Échéances calculées depuis la date de début + intervalles standards.
 * Idempotent (un rappel par type et par contrat).
 */
final class GenererEntretiensAction
{
    /** Intervalles (jours) par type d'entretien. */
    private const INTERVALLES = [
        Entretien::TYPE_VIDANGE => 30,
        Entretien::TYPE_PLAQUETTES => 90,
        Entretien::TYPE_REVISION => 180,
    ];

    public function execute(ContratLeasing $contrat): void
    {
        $debut = Carbon::parse($contrat->date_debut);

        foreach (self::INTERVALLES as $type => $jours) {
            Entretien::updateOrCreate(
                ['contrat_leasing_id' => $contrat->id, 'type' => $type],
                [
                    'client_id' => $contrat->client_id,
                    'moto_id' => $contrat->moto_id,
                    'date_echeance' => $debut->copy()->addDays($jours)->toDateString(),
                ],
            );
        }
    }
}
