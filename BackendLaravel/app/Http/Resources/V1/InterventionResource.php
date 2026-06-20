<?php

namespace App\Http\Resources\V1;

use App\Models\Intervention;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Intervention
 */
class InterventionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'probleme' => $this->probleme,
            'statut' => $this->statut,
            'statut_label' => match ($this->statut) {
                Intervention::STATUT_NOUVELLE => 'Nouvelle',
                Intervention::STATUT_EN_TRAITEMENT => 'En traitement',
                Intervention::STATUT_TERMINEE => 'Terminée',
                default => $this->statut,
            },
            'date_intervention' => $this->date_intervention?->format('Y-m-d'),
            'client' => new ClientResource($this->whenLoaded('client')),
            'moto' => new MotoResource($this->whenLoaded('moto')),
            'technicien' => new UserResource($this->whenLoaded('technicien')),
            'commentaires' => CommentaireResource::collection($this->whenLoaded('commentaires')),
        ];
    }
}
