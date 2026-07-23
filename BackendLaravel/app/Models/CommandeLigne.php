<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ligne de commande : pièce, quantité et prix figé au moment de la commande.
 */
#[Fillable(['commande_id', 'piece_id', 'quantite', 'prix_unitaire'])]
class CommandeLigne extends Model
{
    protected function casts(): array
    {
        return [
            'quantite' => 'integer',
            'prix_unitaire' => 'integer',
        ];
    }

    public function commande(): BelongsTo
    {
        return $this->belongsTo(Commande::class);
    }

    public function piece(): BelongsTo
    {
        return $this->belongsTo(Piece::class);
    }

    /** Sous-total de la ligne. */
    public function getSousTotalAttribute(): int
    {
        return (int) ($this->quantite * $this->prix_unitaire);
    }
}
