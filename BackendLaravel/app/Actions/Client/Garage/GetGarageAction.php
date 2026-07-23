<?php

namespace App\Actions\Client\Garage;

use App\Repositories\Contracts\GarageRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * Cas d'usage : agréger le contenu de « Mon Garage » d'un client.
 */
final class GetGarageAction
{
    public function __construct(
        private readonly GarageRepositoryInterface $garage,
    ) {}

    /**
     * @return array<string, Collection>
     */
    public function execute(int $clientId): array
    {
        return [
            'motos' => $this->garage->motosForClient($clientId),
            'contrats' => $this->garage->contratsForClient($clientId),
            'paiements' => $this->garage->paiementsForClient($clientId),
            'garanties' => $this->garage->garantiesForClient($clientId),
            'documents' => $this->garage->documentsForClient($clientId),
        ];
    }
}
