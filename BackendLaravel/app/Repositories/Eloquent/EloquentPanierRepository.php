<?php

namespace App\Repositories\Eloquent;

use App\Models\Panier;
use App\Models\PanierLigne;
use App\Repositories\Contracts\PanierRepositoryInterface;

class EloquentPanierRepository implements PanierRepositoryInterface
{
    public function firstOrCreateForClient(int $clientId): Panier
    {
        return Panier::firstOrCreate(['client_id' => $clientId]);
    }

    public function loadContents(Panier $panier): Panier
    {
        return $panier->load(['lignes' => fn ($q) => $q->orderBy('id'), 'lignes.piece']);
    }

    public function findLineForPiece(int $panierId, int $pieceId): ?PanierLigne
    {
        return PanierLigne::query()
            ->where('panier_id', $panierId)
            ->where('piece_id', $pieceId)
            ->first();
    }

    public function createLine(int $panierId, int $pieceId, int $quantite): PanierLigne
    {
        return PanierLigne::create([
            'panier_id' => $panierId,
            'piece_id' => $pieceId,
            'quantite' => $quantite,
        ]);
    }

    public function findLine(int $lineId): ?PanierLigne
    {
        return PanierLigne::with('panier')->find($lineId);
    }

    public function saveLine(PanierLigne $line): void
    {
        $line->save();
    }

    public function deleteLine(PanierLigne $line): void
    {
        $line->delete();
    }

    public function clearLines(Panier $panier): void
    {
        $panier->lignes()->delete();
    }
}
