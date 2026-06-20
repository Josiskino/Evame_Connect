<?php

namespace App\Actions\Intervention;

use App\Exceptions\BusinessException;
use App\Models\Intervention;
use App\Repositories\Contracts\InterventionRepositoryInterface;

/**
 * Cas d'usage : mettre à jour une intervention (changer le statut, réassigner...).
 */
final class UpdateInterventionAction
{
    public function __construct(
        private readonly InterventionRepositoryInterface $interventions,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(int $id, array $data): Intervention
    {
        $intervention = $this->interventions->find($id)
            ?? throw new BusinessException('Intervention introuvable.', 404);

        return $this->interventions->update($intervention, $data);
    }
}
