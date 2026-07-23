<?php

namespace App\Http\Resources\V1\Client;

use App\Models\DemandeLeasing;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin DemandeLeasing
 */
class DemandeLeasingResource extends JsonResource
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
            'frequence' => $this->frequence,
            'prix_comptant' => $this->prix_comptant,
            'apport' => $this->apport,
            'montant_finance' => $this->montant_finance,
            'duree_jours' => $this->duree_jours,
            'cout_journalier' => $this->cout_journalier,
            'cout_hebdomadaire' => $this->cout_hebdomadaire,
            'cout_mensuel' => $this->cout_mensuel,
            'cout_total' => $this->cout_total,
            'created_at' => $this->created_at?->format('Y-m-d H:i'),
            'moto' => new MotoCatalogueResource($this->whenLoaded('moto')),
        ];
    }
}
