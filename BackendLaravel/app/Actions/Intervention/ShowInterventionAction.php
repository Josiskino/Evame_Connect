<?php

namespace App\Actions\Intervention;

use App\Exceptions\BusinessException;
use App\Models\Intervention;
use App\Repositories\Contracts\InterventionRepositoryInterface;

final class ShowInterventionAction
{
    public function __construct(
        private readonly InterventionRepositoryInterface $interventions,
    ) {}

    public function execute(int $id): Intervention
    {
        return $this->interventions->find($id)
            ?? throw new BusinessException('Intervention introuvable.', 404);
    }
}
