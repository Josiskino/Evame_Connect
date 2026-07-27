<?php

namespace App\Http\Resources\V1\Client;

use App\Models\CentreSav;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin CentreSav
 */
class CentreSavResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nom' => $this->nom,
            'adresse' => $this->adresse,
            'telephone' => $this->telephone,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'distance_km' => $this->distance_km ?? null, // renseigné lors du tri par distance
            'horaires' => $this->horaires,
        ];
    }
}
