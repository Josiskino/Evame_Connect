<?php

namespace App\Actions\Dashboard;

use App\Repositories\Contracts\DashboardRepositoryInterface;

/**
 * Cas d'usage : agréger les indicateurs du tableau de bord Direction.
 */
final class GetDashboardMetricsAction
{
    public function __construct(
        private readonly DashboardRepositoryInterface $dashboard,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function execute(): array
    {
        return [
            'activite_commerciale' => $this->dashboard->activiteCommerciale(),
            'stock' => $this->dashboard->stock(),
            'leasing' => $this->dashboard->leasing(),
        ];
    }
}
