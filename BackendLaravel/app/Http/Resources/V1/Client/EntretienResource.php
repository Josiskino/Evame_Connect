<?php

namespace App\Http\Resources\V1\Client;

use App\Models\Entretien;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Entretien
 */
class EntretienResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'statut' => $this->statut,
            'date_echeance' => $this->date_echeance?->format('Y-m-d'),
            'libelle_echeance' => $this->libelle_echeance,
            'effectue_le' => $this->effectue_le?->format('Y-m-d'),
            'moto' => new MotoCatalogueResource($this->whenLoaded('moto')),
        ];
    }
}
