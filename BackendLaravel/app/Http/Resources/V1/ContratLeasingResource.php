<?php

namespace App\Http\Resources\V1;

use App\Models\ContratLeasing;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ContratLeasing
 */
class ContratLeasingResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'date_debut' => $this->date_debut?->format('Y-m-d'),
            'duree_jours' => $this->duree_jours,
            'montant_journalier' => $this->montant_journalier,
            'montant_total' => $this->montant_total,
            'frequence' => $this->frequence,
            'statut' => $this->statut,
            // Indicateurs calculés (payé / reste / progression / statut)
            'montant_paye' => $this->montant_paye,
            'montant_restant' => $this->montant_restant,
            'progression' => $this->progression,
            'statut_paiement' => $this->statut_paiement,
            'en_retard' => $this->en_retard,
            'client' => new ClientResource($this->whenLoaded('client')),
            'moto' => new MotoResource($this->whenLoaded('moto')),
            'paiements' => PaiementResource::collection($this->whenLoaded('paiements')),
        ];
    }
}
