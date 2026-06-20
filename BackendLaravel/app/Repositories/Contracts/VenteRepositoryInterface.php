<?php

namespace App\Repositories\Contracts;

use App\Models\Vente;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface VenteRepositoryInterface
{
    public function paginate(int $perPage = 15): LengthAwarePaginator;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Vente;

    public function find(int $id): ?Vente;
}
