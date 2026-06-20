<?php

namespace App\Actions\Leasing;

use App\DTOs\Leasing\RegisterPaiementData;
use App\Exceptions\BusinessException;
use App\Models\ContratLeasing;
use App\Repositories\Contracts\ContratLeasingRepositoryInterface;
use Illuminate\Support\Carbon;

/**
 * Cas d'usage : enregistrer un paiement sur un contrat leasing.
 */
final class RegisterPaiementAction
{
    public function __construct(
        private readonly ContratLeasingRepositoryInterface $contrats,
    ) {}

    public function execute(int $contratId, RegisterPaiementData $data): ContratLeasing
    {
        $contrat = $this->contrats->find($contratId)
            ?? throw new BusinessException('Contrat de leasing introuvable.', 404);

        $this->contrats->addPaiement($contrat, [
            'montant' => $data->montant,
            'date_paiement' => $data->datePaiement ?? Carbon::today()->format('Y-m-d'),
            'user_id' => $data->userId,
        ]);

        return $this->contrats->find($contratId);
    }
}
