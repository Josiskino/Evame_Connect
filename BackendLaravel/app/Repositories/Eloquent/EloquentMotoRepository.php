<?php

namespace App\Repositories\Eloquent;

use App\Models\Moto;
use App\Repositories\Contracts\MotoRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EloquentMotoRepository implements MotoRepositoryInterface
{
    public function paginateCatalogue(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = Moto::query();

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('modele', 'like', "%{$search}%")
                    ->orWhere('marque', 'like', "%{$search}%")
                    ->orWhere('couleur', 'like', "%{$search}%")
                    ->orWhere('cylindree', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['couleur'])) {
            $query->where('couleur', $filters['couleur']);
        }

        if (! empty($filters['famille'])) {
            $query->where('famille', $filters['famille']);
        }

        if (! empty($filters['classe_cc'])) {
            $query->where('classe_cc', $filters['classe_cc']);
        }

        if (! empty($filters['marque'])) {
            $query->where('marque', $filters['marque']);
        }

        if (! empty($filters['disponible'])) {
            $query->where('stock', '>', 0);
        }

        // Filtre par statut de stock : disponible | stock_faible | rupture
        if (! empty($filters['statut'])) {
            match ($filters['statut']) {
                'disponible' => $query->whereColumn('stock', '>', 'seuil_alerte'),
                'stock_faible' => $query->where('stock', '>', 0)->whereColumn('stock', '<=', 'seuil_alerte'),
                'rupture' => $query->where('stock', 0),
                default => null,
            };
        }

        if (! empty($filters['prix_min'])) {
            $query->where('prix', '>=', (int) $filters['prix_min']);
        }

        if (! empty($filters['prix_max'])) {
            $query->where('prix', '<=', (int) $filters['prix_max']);
        }

        return $query->orderBy('modele')->paginate($perPage);
    }

    public function find(int $id): ?Moto
    {
        return Moto::find($id);
    }

    public function lockAndFind(int $id): ?Moto
    {
        return Moto::lockForUpdate()->find($id);
    }

    public function decrementStock(Moto $moto, int $quantity = 1): void
    {
        $moto->decrement('stock', $quantity);
    }
}
