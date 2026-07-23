<?php

namespace App\Repositories\Contracts;

use App\Models\Piece;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface PieceRepositoryInterface
{
    public function paginate(?string $search, int $perPage = 15): LengthAwarePaginator;

    public function find(int $id): ?Piece;

    /** Verrouille la ligne (SELECT ... FOR UPDATE) pour une opération transactionnelle. */
    public function lockAndFind(int $id): ?Piece;

    public function decrementStock(Piece $piece, int $quantity = 1): void;
}
