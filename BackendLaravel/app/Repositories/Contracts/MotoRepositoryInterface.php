<?php

namespace App\Repositories\Contracts;

use App\Models\Moto;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface MotoRepositoryInterface
{
    /**
     * Catalogue paginé avec recherche et filtres.
     *
     * @param  array<string, mixed>  $filters
     */
    public function paginateCatalogue(array $filters, int $perPage = 15): LengthAwarePaginator;

    public function find(int $id): ?Moto;

    /** Verrouille la ligne (SELECT ... FOR UPDATE) pour une opération transactionnelle. */
    public function lockAndFind(int $id): ?Moto;

    public function decrementStock(Moto $moto, int $quantity = 1): void;
}
