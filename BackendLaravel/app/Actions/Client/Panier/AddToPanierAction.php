<?php

namespace App\Actions\Client\Panier;

use App\Exceptions\BusinessException;
use App\Models\Panier;
use App\Repositories\Contracts\PanierRepositoryInterface;
use App\Repositories\Contracts\PieceRepositoryInterface;

/**
 * Cas d'usage : ajouter une pièce au panier (incrémente si déjà présente).
 */
final class AddToPanierAction
{
    public function __construct(
        private readonly PanierRepositoryInterface $paniers,
        private readonly PieceRepositoryInterface $pieces,
    ) {}

    public function execute(int $clientId, int $pieceId, int $quantite): Panier
    {
        $this->pieces->find($pieceId)
            ?? throw new BusinessException('Pièce introuvable.', 404);

        $panier = $this->paniers->firstOrCreateForClient($clientId);
        $ligne = $this->paniers->findLineForPiece($panier->id, $pieceId);

        if ($ligne !== null) {
            $ligne->quantite += $quantite;
            $this->paniers->saveLine($ligne);
        } else {
            $this->paniers->createLine($panier->id, $pieceId, $quantite);
        }

        return $this->paniers->loadContents($panier);
    }
}
