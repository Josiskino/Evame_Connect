<?php

namespace App\Repositories\Eloquent;

use App\Models\Vente;
use App\Repositories\Contracts\VenteRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EloquentVenteRepository implements VenteRepositoryInterface
{
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        // Eager loading pour éviter les N+1 sur la liste ;
        // contrat.paiements requis pour calculer le reste à payer (leasing).
        return Vente::with(['client', 'moto', 'user', 'contrat.paiements'])
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
