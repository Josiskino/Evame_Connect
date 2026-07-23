<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Document du client (contrat, facture, garantie).
 */
#[Fillable([
    'client_id', 'contrat_leasing_id', 'type', 'libelle', 'fichier_url', 'date',
])]
class Document extends Model
{
    public const TYPE_CONTRAT = 'contrat';

    public const TYPE_FACTURE = 'facture';

    public const TYPE_GARANTIE = 'garantie';

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    public function contrat(): BelongsTo
    {
        return $this->belongsTo(ContratLeasing::class, 'contrat_leasing_id');
    }
}
