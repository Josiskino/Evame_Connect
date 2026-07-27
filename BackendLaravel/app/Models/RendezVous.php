<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Rendez-vous d'un client dans un centre SAV.
 */
#[Fillable([
    'client_id', 'centre_sav_id', 'intervention_id', 'creneau', 'statut',
])]
class RendezVous extends Model
{
    protected $table = 'rendez_vous';

    public const STATUT_CONFIRME = 'confirme';

    public const STATUT_ANNULE = 'annule';

    public const STATUT_HONORE = 'honore';

    protected function casts(): array
    {
        return [
            'creneau' => 'datetime',
        ];
    }

    public function centre(): BelongsTo
    {
        return $this->belongsTo(CentreSav::class, 'centre_sav_id');
    }

    public function intervention(): BelongsTo
    {
        return $this->belongsTo(Intervention::class);
    }
}
