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
                    ->orWhere('couleur', 'like', "%{$search}%")
                    ->orWhere('cylindree', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['couleur'])) {
            $query->where('couleur', $filters['couleur']);
        }

        if (! empty($filters['disponible'])) {
            $query->where('stock', '>', 0);
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
