<?php

namespace App\Actions\Client\Leasing;

use App\DTOs\Client\Leasing\CreateDemandeLeasingData;
use App\Exceptions\BusinessException;
use App\Models\DemandeLeasing;
use App\Repositories\Contracts\DemandeLeasingRepositoryInterface;
use App\Repositories\Contracts\MotoRepositoryInterface;
use App\Support\LeasingCalculator;
use App\Support\ReferenceGenerator;

/**
 * Cas d'usage : enregistrer une demande de leasing (statut en_attente).
 */
final class CreateDemandeLeasingAction
{
    public function __construct(
        private readonly MotoRepositoryInterface $motos,
        private readonly DemandeLeasingRepositoryInterface $demandes,
    ) {}

    public function execute(CreateDemandeLeasingData $data): DemandeLeasing
    {
        $moto = $this->motos->find($data->motoId)
            ?? throw new BusinessException('Moto introuvable.', 404);

        if (! $moto->leasing_eligible) {
            throw new BusinessException("Cette moto n'est pas éligible au leasing.", 422);
        }

        $calcul = LeasingCalculator::fromPrix((int) $moto->prix);

        $demande = $this->demandes->create([
            'client_id' => $data->clientId,
            'moto_id' => $moto->id,
            'prix_comptant' => $calcul['prix'],
            'apport' => $calcul['apport'],
            'montant_finance' => $calcul['montant_finance'],
            'duree_jours' => $calcul['duree_jours'],
            'cout_journalier' => $calcul['cout_journalier'],
            'cout_hebdomadaire' => $calcul['cout_hebdomadaire'],
            'cout_mensuel' => $calcul['cout_mensuel'],
            'cout_total' => $calcul['cout_total'],
            'frequence' => $data->frequence,
            'statut' => DemandeLeasing::STATUT_EN_ATTENTE,
        ]);

        // Numéro lisible basé sur l'id (unicité garantie).
        $demande->numero = ReferenceGenerator::make('DEM', $demande->id);
        $demande->save();

        return $demande;
    }
}
