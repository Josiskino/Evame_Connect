<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Garantie d'une moto du client.
 */
#[Fillable([
    'client_id', 'moto_id', 'contrat_leasing_id', 'type', 'date_debut', 'date_fin',
])]
class Garantie extends Model
{
    public const TYPE_MOTEUR = 'moteur';

    public const TYPE_PIECES = 'pieces';

    public const TYPE_GENERALE = 'generale';

    public const STATUT_ACTIVE = 'active';

    public const STATUT_EXPIREE = 'expiree';

    protected $appends = ['statut'];

    protected function casts(): array
    {
        return [
            'date_debut' => 'date',
            'date_fin' => 'date',
        ];
    }

    /** Garantie encore valide ? */
    public function getStatutAttribute(): string
    {
        return $this->date_fin !== null && $this->date_fin->endOfDay()->greaterThanOrEqualTo(Carbon::now())
            ? self::STATUT_ACTIVE
            : self::STATUT_EXPIREE;
    }

    public function moto(): BelongsTo
    {
        return $this->belongsTo(Moto::class);
    }
}
