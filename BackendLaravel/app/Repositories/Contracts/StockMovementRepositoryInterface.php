<?php

namespace App\Repositories\Contracts;

use App\Models\StockMovement;

interface StockMovementRepositoryInterface
{
    /**
     * Enregistre un mouvement de stock.
     *
     * @param  array<string, mixed>  $data
     */
    public function record(array $data): StockMovement;
}
