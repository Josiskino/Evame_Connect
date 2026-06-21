<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\ContratLeasing;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Réaligne, sans destruction, le contrat « scénario » KOFFI sur l'exemple du
 * cahier des charges : 180 j × 2 000 FCFA, payé 200 000, progression 56 %,
 * statut « à jour ». Les dates relatives stockées dérivant avec le temps réel,
 * on replace la date de début à J-80 (attendu 160 000 < payé 200 000 → à jour).
 * Idempotent : relançable sans effet de bord.
 */
class LeasingDemoSeeder extends Seeder
{
    public function run(): void
    {
        $koffi = Client::where('nom', 'KOFFI Mensah')->first();

        if (! $koffi) {
            $this->command?->warn('Client KOFFI Mensah introuvable — rien à réaligner.');

            return;
        }

        $contrat = ContratLeasing::where('client_id', $koffi->id)->latest()->first();

        if (! $contrat) {
            $this->command?->warn('Contrat KOFFI introuvable — rien à réaligner.');

            return;
        }

        $contrat->forceFill([
            'date_debut' => Carbon::today()->subDays(80)->format('Y-m-d'),
        ])->save();

        if ($contrat->vente_id) {
            $contrat->vente?->forceFill([
                'date_vente' => Carbon::today()->subDays(80)->format('Y-m-d'),
            ])->save();
        }

        $this->command?->info('Contrat KOFFI réaligné (statut « à jour », 56 %).');
    }
}
