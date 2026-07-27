<?php

namespace App\Observers;

use App\Actions\Client\Entretien\GenererEntretiensAction;
use App\Models\ContratLeasing;

/**
 * À la création d'un contrat, programme automatiquement les rappels d'entretien.
 */
class ContratLeasingObserver
{
    public function __construct(
        private readonly GenererEntretiensAction $genererEntretiens,
    ) {}

    public function created(ContratLeasing $contrat): void
    {
        $this->genererEntretiens->execute($contrat);
    }
}
