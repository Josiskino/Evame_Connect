<?php

namespace Database\Seeders;

use App\Models\Client;
use Database\Factories\ClientFactory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Met à jour, sans destruction, les clients déjà enregistrés :
 *  - remplace les adresses non togolaises par une adresse au Togo ;
 *  - renseigne les informations CNI manquantes (dates + lieu d'émission).
 * Idempotent : peut être relancé sans dupliquer ni casser les données.
 */
class ClientsTogoSeeder extends Seeder
{
    public function run(): void
    {
        Client::query()->chunkById(100, function ($clients) {
            foreach ($clients as $i => $client) {
                $changes = [];

                // Adresse togolaise si l'actuelle ne l'est pas.
                if (! in_array($client->adresse, ClientFactory::ADRESSES_TOGO, true)) {
                    $changes['adresse'] = ClientFactory::ADRESSES_TOGO[($client->id + $i) % count(ClientFactory::ADRESSES_TOGO)];
                }

                // CNI de démonstration si absente (CNI valide : expire dans le futur).
                if (empty($client->cni_date_expiration)) {
                    $emission = Carbon::today()->subYears(2)->subDays($client->id % 200);
                    $changes['cni_date_emission'] = $emission->format('Y-m-d');
                    $changes['cni_date_expiration'] = $emission->copy()->addYears(10)->format('Y-m-d');
                    $changes['cni_lieu_emission'] = ClientFactory::LIEUX_EMISSION[$client->id % count(ClientFactory::LIEUX_EMISSION)];
                }

                if ($changes) {
                    $client->forceFill($changes)->save();
                }
            }
        });

        $this->command?->info('Clients mis à jour (adresses Togo + CNI).');
    }
}
