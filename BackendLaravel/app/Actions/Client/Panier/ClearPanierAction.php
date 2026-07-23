<?php

namespace App\Actions\Client\Panier;

use App\Models\Panier;
use App\Repositories\Contracts\PanierRepositoryInterface;

/**
 * Cas d'usage : vider le panier du client.
 */
final class ClearPanierAction
{
    public function __construct(
        private readonly PanierRepositoryInterface $paniers,
    ) {}

    public function execute(int $clientId): Panier
    {
        $panier = $this->paniers->firstOrCreateForClient($clientId);
        $this->paniers->clearLines($panier);

        return $this->paniers->loadContents($panier);
    }
}
