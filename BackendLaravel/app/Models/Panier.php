<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Panier persistant du client (un seul par client).
 */
#[Fillable(['client_id'])]
class Panier extends Model
{
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function lignes(): HasMany
    {
        return $this->hasMany(PanierLigne::class);
    }

    /** Total du panier (nécessite les lignes + pièces chargées). */
    public function getTotalAttribute(): int
    {
        return (int) $this->lignes->sum(fn (PanierLigne $l) => $l->quantite * ($l->piece->prix ?? 0));
    }
}
