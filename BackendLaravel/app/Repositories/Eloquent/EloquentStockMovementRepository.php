<?php

namespace App\Repositories\Eloquent;

use App\Models\StockMovement;
use App\Repositories\Contracts\StockMovementRepositoryInterface;

class EloquentStockMovementRepository implements StockMovementRepositoryInterface
{
    public function record(array $data): StockMovement
    {
        return StockMovement::create($data);
    }
}
