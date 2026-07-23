<?php

namespace App\Http\Resources\V1\Client;

use App\Models\Commande;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Commande
 */
class CommandeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'numero' => $this->numero,
            'statut' => $this->statut,
            'total' => $this->total,
            'created_at' => $this->created_at?->format('Y-m-d H:i'),
            'lignes' => CommandeLigneResource::collection($this->whenLoaded('lignes')),
        ];
    }
}
