<?php

namespace App\Actions\Intervention;

use App\DTOs\Intervention\CreateInterventionData;
use App\Models\Intervention;
use App\Repositories\Contracts\InterventionRepositoryInterface;
use Illuminate\Support\Carbon;

final class CreateInterventionAction
{
    public function __construct(
        private readonly InterventionRepositoryInterface $interventions,
    ) {}

    public function execute(CreateInterventionData $data): Intervention
    {
        $intervention = $this->interventions->create([
            'client_id' => $data->clientId,
            'moto_id' => $data->motoId,
            'technicien_id' => $data->technicienId,
            'probleme' => $data->probleme,
            'statut' => $data->statut ?? Intervention::STATUT_NOUVELLE,
            'date_intervention' => $data->dateIntervention ?? Carbon::today()->format('Y-m-d'),
        ]);

        return $this->interventions->find($intervention->id);
    }
}
