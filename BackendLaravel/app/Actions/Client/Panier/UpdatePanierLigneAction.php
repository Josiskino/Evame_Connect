<?php

namespace App\Actions\Client\Panier;

use App\Exceptions\BusinessException;
use App\Models\Panier;
use App\Repositories\Contracts\PanierRepositoryInterface;

/**
 * Cas d'usage : modifier la quantité d'une ligne du panier.
 */
final class UpdatePanierLigneAction
{
    public function __construct(
        private readonly PanierRepositoryInterface $paniers,
    ) {}

    public function execute(int $clientId, int $ligneId, int $quantite): Panier
    {
        $ligne = $this->paniers->findLine($ligneId);

        if ($ligne === null || $ligne->panier->client_id !== $clientId) {
            throw new BusinessException('Ligne de panier introuvable.', 404);
        }

        $ligne->quantite = $quantite;
        $this->paniers->saveLine($ligne);

        return $this->paniers->loadContents($ligne->panier);
    }
}
