<?php

namespace App\Http\Resources\V1;

use App\Models\Vente;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Vente
 */
class VenteResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'mode' => $this->mode,
            'montant' => $this->montant,
            'date_vente' => $this->date_vente?->format('Y-m-d'),
            'statut' => $this->statut,
            'client' => new ClientResource($this->whenLoaded('client')),
            'moto' => new MotoResource($this->whenLoaded('moto')),
            'commercial' => new UserResource($this->whenLoaded('user')),
            'contrat' => new ContratLeasingResource($this->whenLoaded('contrat')),
        ];
    }
}
