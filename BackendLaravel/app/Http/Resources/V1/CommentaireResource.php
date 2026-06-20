<?php

namespace App\Http\Resources\V1;

use App\Models\Commentaire;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Commentaire
 */
class CommentaireResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'contenu' => $this->contenu,
            'date' => $this->created_at?->format('Y-m-d H:i'),
            'auteur' => new UserResource($this->whenLoaded('user')),
        ];
    }
}
