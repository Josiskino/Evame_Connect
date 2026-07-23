<?php

namespace Database\Seeders;

use App\Models\Moto;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Motos imposées par le jeu de test officiel (Annexe A).
 * Idempotent (updateOrCreate sur la référence) : peut être rejoué sans doublon,
 * y compris à côté du catalogue Haojue existant (rend le filtre marque pertinent).
 */
class AnnexeAMotoSeeder extends Seeder
{
    public function run(): void
    {
        $motos = [
            ['reference' => 'EV001', 'marque' => 'TVS', 'modele' => 'HLX125', 'prix' => 780000, 'leasing' => true],
            ['reference' => 'EV002', 'marque' => 'TVS', 'modele' => 'HLX150', 'prix' => 920000, 'leasing' => true],
            ['reference' => 'EV003', 'marque' => 'TVS', 'modele' => 'Apache RTR', 'prix' => 1350000, 'leasing' => true],
            ['reference' => 'EV004', 'marque' => 'Boxer', 'modele' => 'BM150', 'prix' => 850000, 'leasing' => false],
        ];

        foreach ($motos as $m) {
            Moto::updateOrCreate(
                ['reference' => $m['reference']],
                [
                    'slug' => Str::slug($m['marque'].' '.$m['modele']),
                    'marque' => $m['marque'],
                    'modele' => $m['modele'],
                    'famille' => 'Routière',
                    'prix' => $m['prix'],
                    'leasing_eligible' => $m['leasing'],
                    'couleurs' => ['Noir', 'Rouge', 'Bleu'],
                    'stock' => 5,
                    'seuil_alerte' => 2,
                ],
            );
        }
    }
}
