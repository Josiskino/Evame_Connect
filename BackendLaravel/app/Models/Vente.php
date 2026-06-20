<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['client_id', 'moto_id', 'user_id', 'mode', 'montant', 'date_vente', 'statut'])]
class Vente extends Model
{
    /** @use HasFactory<\Database\Factories\VenteFactory> */
    use HasFactory;

    public const MODE_DIRECT = 'direct';
    public const MODE_LEASING = 'leasing';

    protected function casts(): array
    {
        return [
            'montant' => 'integer',
            'date_vente' => 'date',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function moto(): BelongsTo
    {
        return $this->belongsTo(Moto::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function contrat(): HasOne
    {
        return $this->hasOne(ContratLeasing::class);
    }
}
