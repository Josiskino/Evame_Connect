<?php

namespace App\Http\Resources\V1;

use App\Models\Moto;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Moto
 */
class MotoResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'modele' => $this->modele,
            'couleur' => $this->couleur,
            'cylindree' => $this->cylindree,
            'prix' => $this->prix,
            'image_url' => $this->image_url,
            'stock' => $this->stock,
            'seuil_alerte' => $this->seuil_alerte,
            'disponible' => $this->disponible,
            'stock_faible' => $this->stock_faible,
        ];
    }
}
