<?php

namespace App\Actions\Vente;

use App\Repositories\Contracts\VenteRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class ListVentesAction
{
    public function __construct(
        private readonly VenteRepositoryInterface $ventes,
    ) {}

    public function execute(int $perPage = 15): LengthAwarePaginator
    {
        return $this->ventes->paginate($perPage);
    }
}
