<?php

namespace App\Repositories\Eloquent;

use App\Models\DemandeLeasing;
use App\Repositories\Contracts\DemandeLeasingRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EloquentDemandeLeasingRepository implements DemandeLeasingRepositoryInterface
{
    public function create(array $data): DemandeLeasing
    {
        return DemandeLeasing::create($data);
    }

    public function paginateForClient(int $clientId, int $perPage = 15): LengthAwarePaginator
    {
        return DemandeLeasing::query()
            ->where('client_id', $clientId)
            ->with('moto')
            ->latest('id')
            ->paginate($perPage);
    }

    public function findForClient(int $id, int $clientId): ?DemandeLeasing
    {
        return DemandeLeasing::query()
            ->where('id', $id)
            ->where('client_id', $clientId)
            ->with('moto')
            ->first();
    }
}
