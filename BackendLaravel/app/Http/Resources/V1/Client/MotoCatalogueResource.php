<?php

namespace App\Http\Resources\V1\Client;

use App\Models\Moto;
use App\Support\LeasingCalculator;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

/**
 * Fiche moto exposée au client (application mobile).
 * Masque le stock exact : seul `disponible` est exposé.
 *
 * @mixin Moto
 */
class MotoCatalogueResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'marque' => $this->marque,
            'modele' => $this->modele,
            'famille' => $this->famille,
            'classe_cc' => $this->classe_cc,
            'cylindree' => $this->cylindree,
            'puissance' => $this->puissance,
            'couple' => $this->couple,
            'couleur' => $this->couleur,
            'couleurs' => $this->couleurs,
            'prix' => $this->prix,
            'leasing_eligible' => $this->leasing_eligible,
            // Coût mensuel indicatif du leasing (même formule que le simulateur).
            'cout_mensuel_indicatif' => $this->leasing_eligible
                ? LeasingCalculator::coutMensuelIndicatif((int) $this->prix)
                : null,
            'disponible' => $this->disponible,
            'image_url' => $this->resolveImageUrl($this->image_url),
            'images' => collect($this->images ?? [])->map(fn ($p) => $this->resolveImageUrl($p))->all(),
            'specifications' => $this->specifications,
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
