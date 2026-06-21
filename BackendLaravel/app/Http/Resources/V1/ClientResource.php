<?php

namespace App\Http\Resources\V1;

use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Client
 */
class ClientResource extends JsonResource
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
            'adresse' => $this->adresse,
            // Pièce d'identité (CNI).
            'cni_recto_url' => $this->resolveImageUrl($this->cni_recto),
            'cni_verso_url' => $this->resolveImageUrl($this->cni_verso),
            'cni_date_emission' => $this->cni_date_emission?->format('Y-m-d'),
            'cni_date_expiration' => $this->cni_date_expiration?->format('Y-m-d'),
            'cni_lieu_emission' => $this->cni_lieu_emission,
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
