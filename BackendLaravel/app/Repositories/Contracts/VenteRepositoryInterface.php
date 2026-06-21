<?php

namespace App\Repositories\Contracts;

use App\Models\Vente;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface VenteRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator;

    /**
     * Indicateurs agrégés (respectant les mêmes filtres que la liste).
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, int>
     */
    public function stats(array $filters = []): array;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Vente;

    public function find(int $id): ?Vente;
}
