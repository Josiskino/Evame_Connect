<?php

namespace Database\Seeders;

use App\Models\ContratLeasing;
use App\Models\Document;
use App\Models\Garantie;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Données de démonstration pour « Mon Garage » : une garantie moteur (1 an) et
 * un document « contrat » rattachés à chaque contrat de leasing existant.
 * Idempotent.
 */
class GarageDemoSeeder extends Seeder
{
    public function run(): void
    {
        foreach (ContratLeasing::all() as $contrat) {
            $debut = Carbon::parse($contrat->date_debut);

            Garantie::updateOrCreate(
                ['client_id' => $contrat->client_id, 'moto_id' => $contrat->moto_id, 'type' => Garantie::TYPE_MOTEUR],
                [
                    'contrat_leasing_id' => $contrat->id,
                    'date_debut' => $debut->toDateString(),
                    'date_fin' => $debut->copy()->addYear()->toDateString(),
                ],
            );

            Document::updateOrCreate(
                ['client_id' => $contrat->client_id, 'contrat_leasing_id' => $contrat->id, 'type' => Document::TYPE_CONTRAT],
                [
                    'libelle' => 'Contrat de leasing '.$contrat->numero,
                    'date' => $debut->toDateString(),
                ],
            );
        }
    }
}
