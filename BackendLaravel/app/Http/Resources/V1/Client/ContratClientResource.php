<?php

namespace App\Http\Resources\V1\Client;

use App\Http\Resources\V1\PaiementResource;
use App\Models\ContratLeasing;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Contrat de leasing tel qu'exposé au client (Mon Garage).
 *
 * @mixin ContratLeasing
 */
class ContratClientResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'numero' => $this->numero,
            'date_debut' => $this->date_debut?->format('Y-m-d'),
            'duree_jours' => $this->duree_jours,
            'montant_journalier' => $this->montant_journalier,
            'montant_total' => $this->montant_total,
            'frequence' => $this->frequence,
            'statut' => $this->statut,
            'montant_paye' => $this->montant_paye,
            'montant_restant' => $this->montant_restant,
            'progression' => $this->progression,
            'statut_paiement' => $this->statut_paiement,
            'en_retard' => $this->en_retard,
            'moto' => new MotoCatalogueResource($this->whenLoaded('moto')),
            'paiements' => PaiementResource::collection($this->whenLoaded('paiements')),
        ];
    }
}
