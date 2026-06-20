<?php

namespace App\Repositories\Eloquent;

use App\Models\ContratLeasing;
use App\Models\Moto;
use App\Models\Paiement;
use App\Models\Vente;
use App\Repositories\Contracts\DashboardRepositoryInterface;
use Illuminate\Support\Carbon;

class EloquentDashboardRepository implements DashboardRepositoryInterface
{
    public function activiteCommerciale(): array
    {
        $base = fn () => Vente::where('statut', 'validee');

        return [
            'chiffre_affaires_total' => (int) $base()->sum('montant'),
            'nombre_ventes' => $base()->count(),
            'evolution_mensuelle' => $this->evolutionMensuelle(),
        ];
    }

    /** CA et nombre de ventes agrégés par mois sur 6 mois. */
    private function evolutionMensuelle(): array
    {
        $debut = Carbon::today()->subMonths(5)->startOfMonth();

        $rows = Vente::where('statut', 'validee')
            ->where('date_vente', '>=', $debut)
            ->get(['montant', 'date_vente'])
            ->groupBy(fn (Vente $v) => $v->date_vente->format('Y-m'));

        $resultat = [];
        for ($i = 5; $i >= 0; $i--) {
            $mois = Carbon::today()->subMonths($i)->format('Y-m');
            $resultat[] = [
                'mois' => $mois,
                'chiffre_affaires' => (int) ($rows[$mois]?->sum('montant') ?? 0),
                'nombre_ventes' => (int) ($rows[$mois]?->count() ?? 0),
            ];
        }

        return $resultat;
    }

    public function stock(): array
    {
        return [
            'motos_disponibles' => (int) Moto::where('stock', '>', 0)->sum('stock'),
            'references_disponibles' => Moto::where('stock', '>', 0)->count(),
            'motos_vendues' => Vente::where('statut', 'validee')->count(),
            'alertes_stock_faible' => Moto::whereColumn('stock', '<=', 'seuil_alerte')
                ->where('stock', '>', 0)
                ->get(['id', 'modele', 'couleur', 'stock', 'seuil_alerte']),
            'ruptures' => Moto::where('stock', 0)->count(),
        ];
    }

    public function leasing(): array
    {
        $contratsActifs = ContratLeasing::where('statut', 'actif')->with('paiements')->get();
        $enRetard = $contratsActifs->filter(fn (ContratLeasing $c) => $c->en_retard);

        return [
            'contrats_actifs' => $contratsActifs->count(),
            'encaissements_total' => (int) Paiement::sum('montant'),
            'clients_en_retard' => $enRetard->count(),
            'reste_a_recouvrer' => (int) $contratsActifs->sum('montant_restant'),
        ];
    }
}
