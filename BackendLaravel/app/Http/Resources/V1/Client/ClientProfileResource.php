<?php

namespace App\Http\Resources\V1\Client;

use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Profil client tel qu'exposé à l'espace client (application mobile).
 *
 * @mixin Client
 */
class ClientProfileResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nom' => $this->nom,
            'telephone' => $this->telephone,
            'email' => $this->email,
            'ville' => $this->ville,
            'quartier' => $this->quartier,
            'photo_url' => $this->resolveImageUrl($this->photo_url),
            'points_fidelite' => $this->points_fidelite,
            'source' => $this->source,
        ];
    }

    private function resolveImageUrl(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return asset('storage/'.ltrim($path, '/'));
    }
}
