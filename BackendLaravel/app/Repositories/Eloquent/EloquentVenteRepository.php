<?php

namespace App\Repositories\Eloquent;

use App\Models\Vente;
use App\Repositories\Contracts\VenteRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EloquentVenteRepository implements VenteRepositoryInterface
{
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        // Eager loading pour éviter les N+1 sur la liste
        return Vente::with(['client', 'moto', 'user'])
            ->latest('date_vente')
            ->paginate($perPage);
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
