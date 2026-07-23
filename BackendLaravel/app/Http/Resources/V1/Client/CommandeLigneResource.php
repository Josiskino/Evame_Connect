<?php

namespace App\Http\Resources\V1\Client;

use App\Models\CommandeLigne;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin CommandeLigne
 */
class CommandeLigneResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'quantite' => $this->quantite,
            'prix_unitaire' => $this->prix_unitaire,
            'sous_total' => $this->sous_total,
            'piece' => new PieceResource($this->whenLoaded('piece')),
        ];
    }
}
