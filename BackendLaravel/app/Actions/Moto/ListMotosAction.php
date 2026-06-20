<?php

namespace App\Actions\Moto;

use App\Repositories\Contracts\MotoRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Cas d'usage : lister le catalogue motos (recherche + filtres).
 */
final class ListMotosAction
{
    public function __construct(
        private readonly MotoRepositoryInterface $motos,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function execute(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->motos->paginateCatalogue($filters, $perPage);
    }
}
