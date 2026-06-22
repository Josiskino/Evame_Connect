<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Intervention;
use App\Models\Vente;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

class StatsController extends Controller
{
    /** Statistiques commerciales (aide à la décision). */
    public function commercial(): JsonResponse
    {
        $ca = (int) Vente::sum('montant');
        $nb = Vente::count();

        return ApiResponse::success([
            'chiffre_affaires' => $ca,
            'nombre_ventes' => $nb,
            'panier_moyen' => $nb > 0 ? (int) round($ca / $nb) : 0,
            'nombre_direct' => Vente::where('mode', Vente::MODE_DIRECT)->count(),
            'nombre_leasing' => Vente::where('mode', Vente::MODE_LEASING)->count(),
            'evolution_mensuelle' => $this->evolutionMensuelle(),
            'top_motos' => Vente::selectRaw('moto_id, count(*) as total, sum(montant) as ca')
                ->groupBy('moto_id')->orderByDesc('total')->limit(5)->with('moto:id,modele')->get()
                ->map(fn ($v) => [
                    'modele' => $v->moto?->modele ?? '—',
                    'ventes' => (int) $v->total,
                    'chiffre_affaires' => (int) $v->ca,
                ]),
            'par_commercial' => Vente::selectRaw('user_id, count(*) as total, sum(montant) as ca')
                ->groupBy('user_id')->orderByDesc('ca')->with('user:id,name')->get()
                ->map(fn ($v) => [
                    'commercial' => $v->user?->name ?? '—',
                    'ventes' => (int) $v->total,
                    'chiffre_affaires' => (int) $v->ca,
                ]),
        ]);
    }

    /** Statistiques du service après-vente. */
    public function sav(): JsonResponse
    {
        $total = Intervention::count();
        $terminees = Intervention::where('statut', Intervention::STATUT_TERMINEE)->count();

        return ApiResponse::success([
            'total' => $total,
            'nouvelles' => Intervention::where('statut', Intervention::STATUT_NOUVELLE)->count(),
            'en_traitement' => Intervention::where('statut', Intervention::STATUT_EN_TRAITEMENT)->count(),
            'terminees' => $terminees,
            'taux_resolution' => $total > 0 ? (int) round($terminees / $total * 100) : 0,
            'interventions_du_jour' => Intervention::whereDate('date_intervention', Carbon::today())->count(),
            'par_technicien' => Intervention::selectRaw('technicien_id, count(*) as total')
                ->whereNotNull('technicien_id')->groupBy('technicien_id')
                ->orderByDesc('total')->with('technicien:id,name')->get()
                ->map(fn ($i) => [
                    'technicien' => $i->technicien?->name ?? '—',
                    'interventions' => (int) $i->total,
                ]),
        ]);
    }

    /**
     * Évolution du CA et du nombre de ventes sur les 6 derniers mois.
     *
     * @return array<int, array<string, mixed>>
     */
    private function evolutionMensuelle(): array
    {
        $ventes = Vente::where('date_vente', '>=', Carbon::today()->subMonths(5)->startOfMonth())
            ->get(['montant', 'date_vente']);

        $result = [];
        for ($i = 5; $i >= 0; $i--) {
            $mois = Carbon::today()->subMonths($i);
            $key = $mois->format('Y-m');
            $duMois = $ventes->filter(fn ($v) => $v->date_vente?->format('Y-m') === $key);
            $result[] = [
                'mois' => $key,
                'chiffre_affaires' => (int) $duMois->sum('montant'),
                'nombre_ventes' => $duMois->count(),
            ];
        }

        return $result;
    }
}
