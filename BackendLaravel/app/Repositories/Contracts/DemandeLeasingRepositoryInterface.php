<?php

namespace App\Repositories\Contracts;

use App\Models\DemandeLeasing;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface DemandeLeasingRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): DemandeLeasing;

    public function paginateForClient(int $clientId, int $perPage = 15): LengthAwarePaginator;

    public function findForClient(int $id, int $clientId): ?DemandeLeasing;
}
