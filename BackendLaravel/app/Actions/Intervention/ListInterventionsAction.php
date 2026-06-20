<?php

namespace App\Actions\Intervention;

use App\Repositories\Contracts\InterventionRepositoryInterface;
use Illuminate\Support\Collection;

final class ListInterventionsAction
{
    public function __construct(
        private readonly InterventionRepositoryInterface $interventions,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function execute(array $filters, ?int $technicienId = null): Collection
    {
        return $this->interventions->list($filters, $technicienId);
    }
}
