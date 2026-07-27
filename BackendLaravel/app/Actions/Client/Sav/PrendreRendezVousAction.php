<?php

namespace App\Actions\Client\Sav;

use App\Exceptions\BusinessException;
use App\Models\RendezVous;
use App\Repositories\Contracts\CentreSavRepositoryInterface;
use App\Repositories\Contracts\InterventionRepositoryInterface;
use App\Repositories\Contracts\RendezVousRepositoryInterface;
use Illuminate\Support\Carbon;

/**
 * Cas d'usage : prendre un rendez-vous SAV (créneau + confirmation).
 */
final class PrendreRendezVousAction
{
    public function __construct(
        private readonly CentreSavRepositoryInterface $centres,
        private readonly RendezVousRepositoryInterface $rendezVous,
        private readonly InterventionRepositoryInterface $interventions,
    ) {}

    public function execute(int $clientId, int $centreSavId, string $creneau, ?int $interventionId): RendezVous
    {
        $centre = $this->centres->findActive($centreSavId)
            ?? throw new BusinessException('Centre SAV introuvable.', 404);

        $slot = Carbon::parse($creneau);

        if ($slot->isPast()) {
            throw new BusinessException('Le créneau choisi est déjà passé.', 422);
        }

        $plage = $centre->horairesPourJour($slot);
        if ($plage === null) {
            throw new BusinessException('Le centre est fermé ce jour-là.', 422);
        }

        [$ouverture, $fermeture] = $plage;
        $ouv = Carbon::parse($slot->toDateString().' '.$ouverture);
        $ferm = Carbon::parse($slot->toDateString().' '.$fermeture);

        if ($slot->lessThan($ouv) || $slot->greaterThanOrEqualTo($ferm)) {
            throw new BusinessException('Le créneau est hors des horaires du centre.', 422);
        }

        if ($this->rendezVous->countForSlot($centreSavId, $slot) >= $centre->capacite_creneau) {
            throw new BusinessException('Ce créneau est complet.', 422);
        }

        // Rattachement à un dossier de panne existant (facultatif, propriété vérifiée).
        $intervention = null;
        if ($interventionId !== null) {
            $intervention = $this->interventions->find($interventionId);
            if ($intervention === null || $intervention->client_id !== $clientId) {
                throw new BusinessException('Dossier introuvable.', 404);
            }
        }

        $rdv = $this->rendezVous->create([
            'client_id' => $clientId,
            'centre_sav_id' => $centreSavId,
            'intervention_id' => $intervention?->id,
            'creneau' => $slot,
            'statut' => RendezVous::STATUT_CONFIRME,
        ]);

        if ($intervention !== null) {
            $this->interventions->update($intervention, ['centre_sav_id' => $centreSavId]);
        }

        return $rdv->load(['centre', 'intervention']);
    }
}
