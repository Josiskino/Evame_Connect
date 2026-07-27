<?php

namespace Database\Seeders;

use App\Actions\Client\Entretien\GenererEntretiensAction;
use App\Models\ContratLeasing;
use Illuminate\Database\Seeder;

/**
 * Génère les rappels d'entretien pour les contrats existants (backfill).
 * L'Observer s'en charge pour les nouveaux contrats.
 */
class EntretienDemoSeeder extends Seeder
{
    public function run(GenererEntretiensAction $action): void
    {
        foreach (ContratLeasing::all() as $contrat) {
            $action->execute($contrat);
        }
    }
}
