<?php

namespace App\Actions\Vente;

use App\Repositories\Contracts\VenteRepositoryInterface;

final class GetVentesStatsAction
{
    public function __construct(
        private readonly VenteRepositoryInterface $ventes,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, int>
     */
    public function execute(array $filters = []): array
    {
        return $this->ventes->stats($filters);
    }
}
