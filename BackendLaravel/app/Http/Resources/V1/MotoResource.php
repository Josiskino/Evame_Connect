<?php

namespace App\Http\Resources\V1;

use App\Models\Moto;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

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
            'slug' => $this->slug,
            'modele' => $this->modele,
            'famille' => $this->famille,
            'classe_cc' => $this->classe_cc,
            'couleur' => $this->couleur,
            'couleurs' => $this->couleurs,
            'cylindree' => $this->cylindree,
            'puissance' => $this->puissance,
            'couple' => $this->couple,
            'prix' => $this->prix,
            'image_url' => $this->resolveImageUrl($this->image_url),
            'images' => collect($this->images ?? [])->map(fn ($p) => $this->resolveImageUrl($p))->all(),
            'specifications' => $this->specifications,
            'stock' => $this->stock,
            'seuil_alerte' => $this->seuil_alerte,
            'disponible' => $this->disponible,
            'stock_faible' => $this->stock_faible,
        ];
    }

    /** Transforme un chemin de stockage en URL absolue (laisse les URLs http telles quelles). */
    private function resolveImageUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        return asset('storage/'.ltrim($path, '/'));
    }
}
