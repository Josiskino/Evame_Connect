<?php

namespace App\Repositories\Eloquent;

use App\Models\ContratLeasing;
use App\Models\Paiement;
use App\Repositories\Contracts\ContratLeasingRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class EloquentContratLeasingRepository implements ContratLeasingRepositoryInterface
{
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return ContratLeasing::with(['client', 'moto', 'paiements'])
            ->latest()
            ->paginate($perPage);
    }

    public function activeWithPaiements(): Collection
    {
        return ContratLeasing::where('statut', 'actif')
            ->with('paiements')
            ->get();
    }

    public function create(array $data): ContratLeasing
    {
        return ContratLeasing::create($data);
    }

    public function find(int $id): ?ContratLeasing
    {
        return ContratLeasing::with(['client', 'moto', 'paiements'])->find($id);
    }

    public function addPaiement(ContratLeasing $contrat, array $data): Paiement
    {
        return $contrat->paiements()->create($data);
    }
}
