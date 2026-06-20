<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

#[Fillable([
    'client_id', 'moto_id', 'vente_id', 'date_debut', 'duree_jours',
    'montant_journalier', 'montant_total', 'frequence', 'statut',
])]
class ContratLeasing extends Model
{
    /** @use HasFactory<\Database\Factories\ContratLeasingFactory> */
    use HasFactory;

    public const FREQUENCE_JOURNALIER = 'journalier';
    public const FREQUENCE_HEBDOMADAIRE = 'hebdomadaire';
    public const FREQUENCE_MENSUEL = 'mensuel';

    public const STATUT_A_JOUR = 'a_jour';
    public const STATUT_EN_RETARD = 'en_retard';

    protected $appends = ['montant_paye', 'montant_restant', 'progression', 'statut_paiement'];

    protected function casts(): array
    {
        return [
            'date_debut' => 'date',
            'duree_jours' => 'integer',
            'montant_journalier' => 'integer',
            'montant_total' => 'integer',
        ];
    }

    /** Total déjà payé (somme des paiements). */
    public function getMontantPayeAttribute(): int
    {
        return (int) $this->paiements->sum('montant');
    }

    /** Reste à recouvrer. */
    public function getMontantRestantAttribute(): int
    {
        return max(0, $this->montant_total - $this->montant_paye);
    }

    /** Progression du remboursement en pourcentage (0-100). */
    public function getProgressionAttribute(): int
    {
        if ($this->montant_total <= 0) {
            return 0;
        }

        return (int) round($this->montant_paye / $this->montant_total * 100);
    }

    /**
     * Montant théoriquement dû à ce jour (jours écoulés × montant journalier),
     * plafonné au montant total du contrat.
     */
    public function getMontantAttenduAttribute(): int
    {
        $joursEcoules = max(0, $this->date_debut->diffInDays(Carbon::today(), false));
        $joursEcoules = min($joursEcoules, $this->duree_jours);

        return (int) min($this->montant_total, $joursEcoules * $this->montant_journalier);
    }

    /** Statut de paiement : à jour ou en retard. */
    public function getStatutPaiementAttribute(): string
    {
        return $this->montant_paye >= $this->montant_attendu
            ? self::STATUT_A_JOUR
            : self::STATUT_EN_RETARD;
    }

    public function getEnRetardAttribute(): bool
    {
        return $this->statut_paiement === self::STATUT_EN_RETARD;
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function moto(): BelongsTo
    {
        return $this->belongsTo(Moto::class);
    }

    public function vente(): BelongsTo
    {
        return $this->belongsTo(Vente::class);
    }

    public function paiements(): HasMany
    {
        return $this->hasMany(Paiement::class);
    }
}
