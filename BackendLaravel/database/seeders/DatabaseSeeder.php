<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Commentaire;
use App\Models\ContratLeasing;
use App\Models\Intervention;
use App\Models\Moto;
use App\Models\Paiement;
use App\Models\User;
use App\Models\Vente;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // --- 0. Rôles & permissions (Spatie) ------------------------------
        $this->call(RolePermissionSeeder::class);

        // --- 1. Comptes de test (un par rôle) -----------------------------
        $superAdmin = User::create([
            'name' => 'Super Admin EVAME',
            'email' => 'admin@evame.com',
            'password' => Hash::make('password'),
            'telephone' => '+228 90 00 00 00',
        ]);
        $superAdmin->assignRole(User::ROLE_SUPER_ADMIN);

        $manager = User::create([
            'name' => 'Awa DIRECTION',
            'email' => 'manager@evame.com',
            'password' => Hash::make('password'),
            'telephone' => '+228 90 00 00 01',
        ]);
        $manager->assignRole(User::ROLE_MANAGER);

        $commercial = User::create([
            'name' => 'Yao COMMERCIAL',
            'email' => 'commercial@evame.com',
            'password' => Hash::make('password'),
            'telephone' => '+228 90 00 00 02',
        ]);
        $commercial->assignRole(User::ROLE_COMMERCIAL);

        $technicien = User::create([
            'name' => 'Kodjo SAV',
            'email' => 'sav@evame.com',
            'password' => Hash::make('password'),
            'telephone' => '+228 90 00 00 03',
        ]);
        $technicien->assignRole(User::ROLE_SAV);

        // Quelques commerciaux supplémentaires pour le volume
        User::factory(2)->create()->each(fn (User $u) => $u->assignRole(User::ROLE_COMMERCIAL));

        // --- 2. Catalogue motos (vrai catalogue Haojue) -------------------
        $this->call(MotoCatalogueSeeder::class);

        // Moto 125 pour le contrat « scénario » KOFFI ; on garantit un stock suffisant.
        $moto125 = Moto::where('classe_cc', '125CC')->inRandomOrder()->first()
            ?? Moto::inRandomOrder()->firstOrFail();
        $moto125->update(['stock' => max(8, $moto125->stock)]);

        // --- 3. Clients ---------------------------------------------------
        $koffi = Client::create([
            'nom' => 'KOFFI Mensah',
            'telephone' => '+228 91 23 45 67',
            'email' => 'koffi.mensah@example.com',
            'adresse' => 'Lomé',
        ]);

        Client::factory(15)->create();

        // --- 4. Ventes (historique sur 6 mois) ----------------------------
        Vente::factory(30)->create();

        // --- 5. Contrat leasing « scénario » du cahier des charges --------
        // KOFFI Mensah / EVAME 125 CC / 180 jours / 2 000 FCFA/jour / 360 000 FCFA
        // Débuté il y a 100 jours -> attendu = 200 000 ; payé = 200 000 -> à jour ; progression 56 %.
        $venteLeasing = Vente::create([
            'client_id' => $koffi->id,
            'moto_id' => $moto125->id,
            'user_id' => $commercial->id,
            'mode' => Vente::MODE_LEASING,
            'montant' => 360_000,
            'date_vente' => Carbon::today()->subDays(100)->format('Y-m-d'),
            'statut' => 'validee',
        ]);

        $contratKoffi = ContratLeasing::create([
            'client_id' => $koffi->id,
            'moto_id' => $moto125->id,
            'vente_id' => $venteLeasing->id,
            'date_debut' => Carbon::today()->subDays(100)->format('Y-m-d'),
            'duree_jours' => 180,
            'montant_journalier' => 2_000,
            'montant_total' => 360_000,
            'frequence' => ContratLeasing::FREQUENCE_JOURNALIER,
            'statut' => 'actif',
        ]);

        // Paiements totalisant 200 000 FCFA
        Paiement::create([
            'contrat_leasing_id' => $contratKoffi->id,
            'user_id' => $commercial->id,
            'montant' => 120_000,
            'date_paiement' => Carbon::today()->subDays(60)->format('Y-m-d'),
        ]);
        Paiement::create([
            'contrat_leasing_id' => $contratKoffi->id,
            'user_id' => $commercial->id,
            'montant' => 80_000,
            'date_paiement' => Carbon::today()->subDays(20)->format('Y-m-d'),
        ]);

        // Un contrat « en retard » pour la démo du tableau de bord
        $clientRetard = Client::factory()->create(['nom' => 'AGBO Komla']);
        $motoRetard = Moto::where('id', '!=', $moto125->id)->inRandomOrder()->first() ?? $moto125;
        $contratRetard = ContratLeasing::create([
            'client_id' => $clientRetard->id,
            'moto_id' => $motoRetard->id,
            'date_debut' => Carbon::today()->subDays(90)->format('Y-m-d'),
            'duree_jours' => 180,
            'montant_journalier' => 2_500,
            'montant_total' => 450_000,
            'frequence' => ContratLeasing::FREQUENCE_HEBDOMADAIRE,
            'statut' => 'actif',
        ]);
        // attendu ~225 000, payé seulement 50 000 -> en retard
        Paiement::create([
            'contrat_leasing_id' => $contratRetard->id,
            'user_id' => $commercial->id,
            'montant' => 50_000,
            'date_paiement' => Carbon::today()->subDays(70)->format('Y-m-d'),
        ]);

        // --- 6. Interventions SAV -----------------------------------------
        // Interventions du jour pour le technicien (les 3 statuts)
        Intervention::factory(2)->duJour()->state(['technicien_id' => $technicien->id])->create();
        Intervention::factory()->duJour()->nouvelle()->state(['technicien_id' => $technicien->id])->create();
        // Historique
        Intervention::factory(8)->state(['technicien_id' => $technicien->id])->create();

        // Quelques commentaires sur les interventions en traitement / terminées
        Intervention::where('statut', '!=', Intervention::STATUT_NOUVELLE)
            ->get()
            ->each(function (Intervention $intervention) use ($technicien) {
                Commentaire::create([
                    'intervention_id' => $intervention->id,
                    'user_id' => $technicien->id,
                    'contenu' => 'Diagnostic effectué, pièce commandée.',
                ]);
            });
    }
}
