<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Centre de service après-vente (SAV).
 */
#[Fillable([
    'nom', 'adresse', 'latitude', 'longitude', 'telephone', 'horaires', 'capacite_creneau', 'actif',
])]
class CentreSav extends Model
{
    protected $table = 'centres_sav';

    /** Jour ISO (1=lundi … 7=dimanche) -> clé des horaires. */
    public const JOURS = [
        1 => 'lundi', 2 => 'mardi', 3 => 'mercredi', 4 => 'jeudi',
        5 => 'vendredi', 6 => 'samedi', 7 => 'dimanche',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'horaires' => 'array',
            'capacite_creneau' => 'integer',
            'actif' => 'boolean',
        ];
    }

    /**
     * Plage d'ouverture [ouverture, fermeture] pour une date donnée, ou null si fermé.
     *
     * @return array{0:string, 1:string}|null
     */
    public function horairesPourJour(Carbon $date): ?array
    {
        $jour = self::JOURS[$date->dayOfWeekIso] ?? null;
        $plage = $jour ? ($this->horaires[$jour] ?? null) : null;

        return is_array($plage) && count($plage) === 2 ? $plage : null;
    }
}
