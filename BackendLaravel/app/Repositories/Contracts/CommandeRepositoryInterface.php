<?php

namespace App\Repositories\Contracts;

use App\Models\Commande;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface CommandeRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Commande;

    public function paginateForClient(int $clientId, int $perPage = 15): LengthAwarePaginator;

    public function findForClient(int $id, int $clientId): ?Commande;

    public function countInProgressForClient(int $clientId): int;
}
