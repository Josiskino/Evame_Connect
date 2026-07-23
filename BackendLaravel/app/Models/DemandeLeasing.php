<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Demande de leasing soumise par un client depuis l'application mobile.
 * En attente de traitement par EVAME ; une demande approuvée donnera lieu
 * à un ContratLeasing actif (côté admin, ultérieurement).
 */
#[Fillable([
    'client_id', 'moto_id', 'numero', 'prix_comptant', 'apport', 'montant_finance',
    'duree_jours', 'cout_journalier', 'cout_hebdomadaire', 'cout_mensuel', 'cout_total',
    'frequence', 'statut',
])]
class DemandeLeasing extends Model
{
    public const FREQUENCE_JOURNALIER = 'journalier';

    public const FREQUENCE_HEBDOMADAIRE = 'hebdomadaire';

    public const FREQUENCE_MENSUEL = 'mensuel';

    public const FREQUENCES = [
        self::FREQUENCE_JOURNALIER,
        self::FREQUENCE_HEBDOMADAIRE,
        self::FREQUENCE_MENSUEL,
    ];

    public const STATUT_EN_ATTENTE = 'en_attente';

    public const STATUT_APPROUVEE = 'approuvee';

    public const STATUT_REFUSEE = 'refusee';

    protected function casts(): array
    {
        return [
            'prix_comptant' => 'integer',
            'apport' => 'integer',
            'montant_finance' => 'integer',
            'duree_jours' => 'integer',
            'cout_journalier' => 'integer',
            'cout_hebdomadaire' => 'integer',
            'cout_mensuel' => 'integer',
            'cout_total' => 'integer',
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
}
