<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ligne du panier : une pièce et sa quantité.
 */
#[Fillable(['panier_id', 'piece_id', 'quantite'])]
class PanierLigne extends Model
{
    protected function casts(): array
    {
        return [
            'quantite' => 'integer',
        ];
    }

    public function panier(): BelongsTo
    {
        return $this->belongsTo(Panier::class);
    }

    public function piece(): BelongsTo
    {
        return $this->belongsTo(Piece::class);
    }

    /** Sous-total de la ligne. */
    public function getSousTotalAttribute(): int
    {
        return (int) ($this->quantite * ($this->piece->prix ?? 0));
    }
}
