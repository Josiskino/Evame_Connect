<?php

namespace App\Http\Resources\V1\Client;

use App\Models\Panier;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Panier
 */
class PanierResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nb_articles' => (int) $this->lignes->sum('quantite'),
            'total' => $this->total,
            'lignes' => $this->lignes->map(fn ($ligne) => [
                'id' => $ligne->id,
                'quantite' => $ligne->quantite,
                'sous_total' => $ligne->sous_total,
                'piece' => new PieceResource($ligne->piece),
            ])->all(),
        ];
    }
}
