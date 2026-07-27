<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Rappel d'entretien généré automatiquement pour une moto sous contrat.
 */
#[Fillable([
    'client_id', 'moto_id', 'contrat_leasing_id', 'type', 'date_echeance', 'effectue_le',
])]
class Entretien extends Model
{
    public const TYPE_VIDANGE = 'vidange';

    public const TYPE_PLAQUETTES = 'plaquettes';

    public const TYPE_REVISION = 'revision';

    public const STATUT_A_VENIR = 'a_venir';

    public const STATUT_DU = 'du';

    public const STATUT_EN_RETARD = 'en_retard';

    public const STATUT_EFFECTUE = 'effectue';

    /** Fenêtre (jours) avant l'échéance à partir de laquelle l'entretien est « à faire ». */
    public const FENETRE_DU_JOURS = 7;

    protected $appends = ['statut', 'libelle_echeance'];

    protected function casts(): array
    {
        return [
            'date_echeance' => 'date',
            'effectue_le' => 'date',
        ];
    }

    /** Nombre de jours (signé) d'aujourd'hui jusqu'à l'échéance (négatif si dépassée). */
    private function joursRestants(): int
    {
        return (int) Carbon::today()->diffInDays($this->date_echeance->copy()->startOfDay(), false);
    }

    public function getStatutAttribute(): string
    {
        if ($this->effectue_le !== null) {
            return self::STATUT_EFFECTUE;
        }

        $jours = $this->joursRestants();

        return match (true) {
            $jours < 0 => self::STATUT_EN_RETARD,
            $jours <= self::FENETRE_DU_JOURS => self::STATUT_DU,
            default => self::STATUT_A_VENIR,
        };
    }

    public function getLibelleEcheanceAttribute(): string
    {
        if ($this->effectue_le !== null) {
            return 'Effectué le '.$this->effectue_le->format('d/m/Y');
        }

        $jours = $this->joursRestants();

        return match (true) {
            $jours < 0 => 'En retard de '.abs($jours).' jour'.(abs($jours) > 1 ? 's' : ''),
            $jours === 0 => "À effectuer aujourd'hui",
            default => 'À effectuer dans '.$jours.' jour'.($jours > 1 ? 's' : ''),
        };
    }

    public function moto(): BelongsTo
    {
        return $this->belongsTo(Moto::class);
    }
}
