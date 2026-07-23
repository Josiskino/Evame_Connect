<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * Pièce détachée (boutique client).
 */
#[Fillable([
    'reference', 'designation', 'prix', 'image_url', 'stock', 'compatibilite',
])]
class Piece extends Model
{
    protected $appends = ['disponible'];

    protected function casts(): array
    {
        return [
            'prix' => 'integer',
            'stock' => 'integer',
        ];
    }

    /** Pièce disponible à la commande. */
    public function getDisponibleAttribute(): bool
    {
        return $this->stock > 0;
    }
}
