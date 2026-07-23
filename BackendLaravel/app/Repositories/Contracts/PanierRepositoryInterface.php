<?php

namespace App\Repositories\Contracts;

use App\Models\Panier;
use App\Models\PanierLigne;

interface PanierRepositoryInterface
{
    public function firstOrCreateForClient(int $clientId): Panier;

    /** Recharge le panier avec ses lignes et les pièces associées. */
    public function loadContents(Panier $panier): Panier;

    public function findLineForPiece(int $panierId, int $pieceId): ?PanierLigne;

    public function createLine(int $panierId, int $pieceId, int $quantite): PanierLigne;

    /** Ligne avec son panier chargé (pour contrôle de propriété). */
    public function findLine(int $lineId): ?PanierLigne;

    public function saveLine(PanierLigne $line): void;

    public function deleteLine(PanierLigne $line): void;

    public function clearLines(Panier $panier): void;
}
