<?php

namespace App\Http\Resources\V1\Client;

use App\Models\Piece;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Pièce détachée exposée au client. Masque le stock exact (expose `disponible`).
 *
 * @mixin Piece
 */
class PieceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'designation' => $this->designation,
            'prix' => $this->prix,
            'compatibilite' => $this->compatibilite,
            'disponible' => $this->disponible,
            'image_url' => $this->resolveImageUrl($this->image_url),
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
