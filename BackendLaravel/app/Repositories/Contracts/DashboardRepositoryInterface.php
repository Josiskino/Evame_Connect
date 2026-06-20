<?php

namespace App\Repositories\Contracts;

interface DashboardRepositoryInterface
{
    /** @return array{chiffre_affaires_total:int, nombre_ventes:int, evolution_mensuelle:array<int, array<string, mixed>>} */
    public function activiteCommerciale(): array;

    /** @return array<string, mixed> */
    public function stock(): array;

    /** @return array<string, mixed> */
    public function leasing(): array;
}
