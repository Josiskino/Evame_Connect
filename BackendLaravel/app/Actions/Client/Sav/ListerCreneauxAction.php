<?php

namespace App\Actions\Client\Sav;

use App\Models\CentreSav;
use App\Repositories\Contracts\RendezVousRepositoryInterface;
use Illuminate\Support\Carbon;

/**
 * Cas d'usage : générer les créneaux disponibles d'un centre pour une date.
 * Découpe la plage d'ouverture par pas de 30 min et exclut les créneaux complets.
 */
final class ListerCreneauxAction
{
    private const PAS_MINUTES = 30;

    public function __construct(
        private readonly RendezVousRepositoryInterface $rendezVous,
    ) {}

    /**
     * @return array<int, array{heure:string, disponible:bool}>
     */
    public function execute(CentreSav $centre, string $date): array
    {
        $jour = Carbon::parse($date)->startOfDay();
        $plage = $centre->horairesPourJour($jour);

        if ($plage === null) {
            return []; // fermé ce jour-là
        }

        [$ouverture, $fermeture] = $plage;
        $counts = $this->rendezVous->confirmedCountsForDay($centre->id, $jour);

        $creneaux = [];
        $courant = Carbon::parse($jour->toDateString().' '.$ouverture);
        $fin = Carbon::parse($jour->toDateString().' '.$fermeture);

        while ($courant->lessThan($fin)) {
            $heure = $courant->format('H:i');
            $pris = (int) ($counts[$heure] ?? 0);

            $creneaux[] = [
                'heure' => $heure,
                'disponible' => $pris < $centre->capacite_creneau && $courant->isFuture(),
            ];

            $courant->addMinutes(self::PAS_MINUTES);
        }

        return $creneaux;
    }
}
