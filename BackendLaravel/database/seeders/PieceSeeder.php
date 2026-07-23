<?php

namespace Database\Seeders;

use App\Models\Piece;
use Illuminate\Database\Seeder;

/**
 * Pièces détachées du jeu de test officiel (Annexe A). Idempotent.
 */
class PieceSeeder extends Seeder
{
    public function run(): void
    {
        $pieces = [
            ['reference' => 'P001', 'designation' => 'Filtre à huile', 'prix' => 5000, 'compatibilite' => 'TVS HLX125, HLX150'],
            ['reference' => 'P002', 'designation' => 'Plaquettes frein', 'prix' => 8500, 'compatibilite' => 'TVS Apache RTR, Boxer BM150'],
            ['reference' => 'P003', 'designation' => 'Bougie', 'prix' => 2500, 'compatibilite' => 'Toutes motos essence'],
            ['reference' => 'P004', 'designation' => 'Kit chaîne', 'prix' => 35000, 'compatibilite' => 'TVS HLX125, HLX150, Apache RTR'],
            ['reference' => 'P005', 'designation' => 'Batterie', 'prix' => 28000, 'compatibilite' => 'TVS, Boxer, Haojue'],
        ];

        foreach ($pieces as $p) {
            Piece::updateOrCreate(
                ['reference' => $p['reference']],
                [
                    'designation' => $p['designation'],
                    'prix' => $p['prix'],
                    'compatibilite' => $p['compatibilite'],
                    'stock' => 25,
                ],
            );
        }
    }
}
