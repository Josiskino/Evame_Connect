<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Commande de pièces détachées (paiement non demandé).
 */
#[Fillable(['client_id', 'numero', 'statut', 'total'])]
class Commande extends Model
{
    public const STATUT_SOUMISE = 'soumise';

    protected function casts(): array
    {
        return [
            'total' => 'integer',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function lignes(): HasMany
    {
        return $this->hasMany(CommandeLigne::class);
    }
}
