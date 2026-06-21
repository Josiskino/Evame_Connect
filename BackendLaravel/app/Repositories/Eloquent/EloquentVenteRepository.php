<?php

namespace App\Repositories\Eloquent;

use App\Models\Vente;
use App\Repositories\Contracts\VenteRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class EloquentVenteRepository implements VenteRepositoryInterface
{
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        // Eager loading pour éviter les N+1 sur la liste ;
        // contrat.paiements requis pour calculer le reste à payer (leasing).
        return $this->applyFilters(Vente::query(), $filters)
            ->with(['client', 'moto', 'user', 'contrat.paiements'])
            ->latest('date_vente')
            ->paginate($perPage);
    }

    public function stats(array $filters = []): array
    {
        $base = fn () => $this->applyFilters(Vente::query(), $filters);

        return [
            'chiffre_affaires' => (int) $base()->sum('montant'),
            'nombre_ventes' => $base()->count(),
            'nombre_direct' => (clone $base())->where('mode', Vente::MODE_DIRECT)->count(),
            'nombre_leasing' => (clone $base())->where('mode', Vente::MODE_LEASING)->count(),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function applyFilters(Builder $query, array $filters): Builder
    {
        if (! empty($filters['mode'])) {
            $query->where('mode', $filters['mode']);
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('date_vente', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('date_vente', '<=', $filters['date_to']);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->whereHas('client', fn ($c) => $c->where('nom', 'like', "%{$search}%"))
                    ->orWhereHas('moto', fn ($m) => $m->where('modele', 'like', "%{$search}%"));
            });
        }

        return $query;
    }

    public function create(array $data): Vente
    {
        return Vente::create($data);
    }

    public function find(int $id): ?Vente
    {
        return Vente::with(['client', 'moto', 'user', 'contrat'])->find($id);
    }
}
