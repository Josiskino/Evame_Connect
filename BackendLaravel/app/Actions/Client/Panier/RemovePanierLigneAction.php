<?php

namespace App\Actions\Client\Panier;

use App\Exceptions\BusinessException;
use App\Models\Panier;
use App\Repositories\Contracts\PanierRepositoryInterface;

/**
 * Cas d'usage : retirer une ligne du panier.
 */
final class RemovePanierLigneAction
{
    public function __construct(
        private readonly PanierRepositoryInterface $paniers,
    ) {}

    public function execute(int $clientId, int $ligneId): Panier
    {
        $ligne = $this->paniers->findLine($ligneId);

        if ($ligne === null || $ligne->panier->client_id !== $clientId) {
            throw new BusinessException('Ligne de panier introuvable.', 404);
        }

        $panier = $ligne->panier;
        $this->paniers->deleteLine($ligne);

        return $this->paniers->loadContents($panier);
    }
}
