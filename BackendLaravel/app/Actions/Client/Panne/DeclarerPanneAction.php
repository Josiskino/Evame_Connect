<?php

namespace App\Actions\Client\Panne;

use App\DTOs\Client\Panne\DeclarerPanneData;
use App\Events\ResourceChanged;
use App\Models\Intervention;
use App\Repositories\Contracts\InterventionRepositoryInterface;
use App\Support\ReferenceGenerator;
use Illuminate\Support\Carbon;

/**
 * Cas d'usage : déclarer une panne (crée une intervention SAV côté client).
 */
final class DeclarerPanneAction
{
    public function __construct(
        private readonly InterventionRepositoryInterface $interventions,
    ) {}

    public function execute(DeclarerPanneData $data): Intervention
    {
        $intervention = $this->interventions->create([
            'client_id' => $data->clientId,
            'moto_id' => $data->motoId,
            'probleme' => $data->description,
            'categorie' => $data->categorie,
            'urgence' => $data->urgence,
            'photo_url' => $data->photoUrl,
            'source' => Intervention::SOURCE_CLIENT,
            'statut' => Intervention::STATUT_NOUVELLE,
            'date_intervention' => Carbon::today()->toDateString(),
        ]);

        // Numéro de dossier basé sur l'id (unicité garantie).
        $intervention->numero_dossier = ReferenceGenerator::make('DOS', $intervention->id);
        $intervention->save();

        // Le staff voit la nouvelle panne en temps réel (feed d'activité).
        event(new ResourceChanged('intervention', 'created', $intervention->id, $intervention->numero_dossier));

        return $intervention;
    }
}
