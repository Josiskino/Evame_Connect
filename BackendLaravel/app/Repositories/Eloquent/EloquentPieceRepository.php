<?php

namespace App\Repositories\Eloquent;

use App\Models\Piece;
use App\Repositories\Contracts\PieceRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EloquentPieceRepository implements PieceRepositoryInterface
{
    public function paginate(?string $search, int $perPage = 15): LengthAwarePaginator
    {
        $query = Piece::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('designation', 'like', "%{$search}%")
                    ->orWhere('reference', 'like', "%{$search}%")
                    ->orWhere('compatibilite', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('designation')->paginate($perPage);
    }

    public function find(int $id): ?Piece
    {
        return Piece::find($id);
    }

    public function lockAndFind(int $id): ?Piece
    {
        return Piece::lockForUpdate()->find($id);
    }

    public function decrementStock(Piece $piece, int $quantity = 1): void
    {
        $piece->decrement('stock', $quantity);
    }
}
