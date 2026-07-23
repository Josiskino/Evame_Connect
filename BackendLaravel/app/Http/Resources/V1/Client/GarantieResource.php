<?php

namespace App\Http\Resources\V1\Client;

use App\Models\Garantie;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Garantie
 */
class GarantieResource extends JsonResource
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
            'date_debut' => $this->date_debut?->format('Y-m-d'),
            'date_fin' => $this->date_fin?->format('Y-m-d'),
            'moto' => new MotoCatalogueResource($this->whenLoaded('moto')),
        ];
    }
}
