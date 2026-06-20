<?php

namespace App\Actions\Leasing;

use App\DTOs\Leasing\CreateContratData;
use App\Models\ContratLeasing;
use App\Repositories\Contracts\ContratLeasingRepositoryInterface;

/**
 * Cas d'usage : créer un contrat leasing.
 * Le montant total est calculé automatiquement : durée × montant journalier.
 */
final class CreateContratAction
{
    public function __construct(
        private readonly ContratLeasingRepositoryInterface $contrats,
    ) {}

    public function execute(CreateContratData $data): ContratLeasing
    {
        $contrat = $this->contrats->create([
            'client_id' => $data->clientId,
            'moto_id' => $data->motoId,
            'vente_id' => $data->venteId,
            'date_debut' => $data->dateDebut,
            'duree_jours' => $data->dureeJours,
            'montant_journalier' => $data->montantJournalier,
            'montant_total' => $data->dureeJours * $data->montantJournalier,
            'frequence' => $data->frequence,
            'statut' => 'actif',
        ]);

        return $this->contrats->find($contrat->id);
    }
}
