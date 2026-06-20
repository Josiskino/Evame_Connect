<?php

namespace App\Actions\Intervention;

use App\Exceptions\BusinessException;
use App\Models\Intervention;
use App\Repositories\Contracts\InterventionRepositoryInterface;

/**
 * Cas d'usage : ajouter un commentaire à une intervention.
 */
final class AddCommentaireAction
{
    public function __construct(
        private readonly InterventionRepositoryInterface $interventions,
    ) {}

    public function execute(int $interventionId, string $contenu, int $userId): Intervention
    {
        $intervention = $this->interventions->find($interventionId)
            ?? throw new BusinessException('Intervention introuvable.', 404);

        $this->interventions->addCommentaire($intervention, [
            'contenu' => $contenu,
            'user_id' => $userId,
        ]);

        return $this->interventions->find($interventionId);
    }
}
