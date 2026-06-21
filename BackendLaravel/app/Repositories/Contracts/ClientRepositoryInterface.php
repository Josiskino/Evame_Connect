<?php

namespace App\Repositories\Contracts;

use App\Models\Client;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ClientRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(?string $search, int $perPage = 15, array $filters = []): LengthAwarePaginator;

    /**
     * @return array<string, int>
     */
    public function stats(): array;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Client;

    public function find(int $id): ?Client;
}
