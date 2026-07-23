<?php

namespace App\Actions\Client\Panier;

use App\Models\Panier;
use App\Repositories\Contracts\PanierRepositoryInterface;

/**
 * Cas d'usage : récupérer (ou initialiser) le panier du client avec son contenu.
 */
final class GetPanierAction
{
    public function __construct(
        private readonly PanierRepositoryInterface $paniers,
    ) {}

    public function execute(int $clientId): Panier
    {
        $panier = $this->paniers->firstOrCreateForClient($clientId);

        return $this->paniers->loadContents($panier);
    }
}
