<?php

namespace App\Http\Resources\V1;

use App\Models\Paiement;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Paiement
 */
class PaiementResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'montant' => $this->montant,
            'date_paiement' => $this->date_paiement?->format('Y-m-d'),
        ];
    }
}
