<?php

namespace Database\Seeders;

use App\Models\CentreSav;
use Illuminate\Database\Seeder;

/**
 * Centres SAV du jeu de test (Annexe A). Coordonnées calées pour retomber
 * sur ~3 / 6 / 8 km depuis le quartier Agoè (6.2000, 1.2000). Idempotent.
 */
class CentresSavSeeder extends Seeder
{
    public function run(): void
    {
        // Lun–Sam 08:00–17:00, dimanche fermé.
        $horaires = [
            'lundi' => ['08:00', '17:00'],
            'mardi' => ['08:00', '17:00'],
            'mercredi' => ['08:00', '17:00'],
            'jeudi' => ['08:00', '17:00'],
            'vendredi' => ['08:00', '17:00'],
            'samedi' => ['08:00', '13:00'],
            'dimanche' => null,
        ];

        $centres = [
            ['nom' => 'EVAME Agoè', 'adresse' => 'Agoè, Lomé', 'latitude' => 6.2269800, 'longitude' => 1.2000000, 'telephone' => '+228 90 00 00 01'],
            ['nom' => 'EVAME Adidogomé', 'adresse' => 'Adidogomé, Lomé', 'latitude' => 6.2539600, 'longitude' => 1.2000000, 'telephone' => '+228 90 00 00 02'],
            ['nom' => 'EVAME Bè', 'adresse' => 'Bè, Lomé', 'latitude' => 6.2719500, 'longitude' => 1.2000000, 'telephone' => '+228 90 00 00 03'],
        ];

        foreach ($centres as $c) {
            CentreSav::updateOrCreate(
                ['nom' => $c['nom']],
                [
                    'adresse' => $c['adresse'],
                    'latitude' => $c['latitude'],
                    'longitude' => $c['longitude'],
                    'telephone' => $c['telephone'],
                    'horaires' => $horaires,
                    'capacite_creneau' => 30,
                    'actif' => true,
                ],
            );
        }
    }
}
