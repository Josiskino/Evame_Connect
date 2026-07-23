<?php

namespace App\Repositories\Eloquent;

use App\Models\Commande;
use App\Repositories\Contracts\CommandeRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EloquentCommandeRepository implements CommandeRepositoryInterface
{
    public function create(array $data): Commande
    {
        return Commande::create($data);
    }

    public function paginateForClient(int $clientId, int $perPage = 15): LengthAwarePaginator
    {
        return Commande::query()
            ->where('client_id', $clientId)
            ->with('lignes.piece')
            ->latest('id')
            ->paginate($perPage);
    }

    public function findForClient(int $id, int $clientId): ?Commande
    {
        return Commande::query()
            ->where('id', $id)
            ->where('client_id', $clientId)
            ->with('lignes.piece')
            ->first();
    }

    public function countInProgressForClient(int $clientId): int
    {
        return Commande::query()
            ->where('client_id', $clientId)
            ->where('statut', Commande::STATUT_SOUMISE)
            ->count();
    }
}
