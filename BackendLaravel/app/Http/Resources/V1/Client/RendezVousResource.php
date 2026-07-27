<?php

namespace App\Http\Resources\V1\Client;

use App\Models\RendezVous;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin RendezVous
 */
class RendezVousResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'creneau' => $this->creneau?->format('Y-m-d H:i'),
            'statut' => $this->statut,
            'centre' => new CentreSavResource($this->whenLoaded('centre')),
            'intervention' => new InterventionClientResource($this->whenLoaded('intervention')),
        ];
    }
}
