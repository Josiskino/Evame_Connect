<?php

namespace App\Actions\Client;

use App\Repositories\Contracts\ClientRepositoryInterface;

final class GetClientsStatsAction
{
    public function __construct(
        private readonly ClientRepositoryInterface $clients,
    ) {}

    /**
     * @return array<string, int>
     */
    public function execute(): array
    {
        return $this->clients->stats();
    }
}
