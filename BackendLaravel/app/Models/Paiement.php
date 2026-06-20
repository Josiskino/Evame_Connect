<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['contrat_leasing_id', 'user_id', 'montant', 'date_paiement'])]
class Paiement extends Model
{
    /** @use HasFactory<\Database\Factories\PaiementFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'montant' => 'integer',
            'date_paiement' => 'date',
        ];
    }

    public function contrat(): BelongsTo
    {
        return $this->belongsTo(ContratLeasing::class, 'contrat_leasing_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
