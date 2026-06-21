<?php

namespace App\Actions\Leasing;

use App\Repositories\Contracts\ContratLeasingRepositoryInterface;

final class GetLeasingStatsAction
{
    public function __construct(
        private readonly ContratLeasingRepositoryInterface $contrats,
    ) {}

    /**
     * @return array<string, int>
     */
    public function execute(): array
    {
        return $this->contrats->stats();
    }
}
