<?php

namespace App\Models;

use Database\Factories\InterventionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'client_id', 'moto_id', 'technicien_id', 'centre_sav_id', 'numero_dossier', 'probleme',
    'categorie', 'urgence', 'photo_url', 'source', 'statut', 'date_intervention',
])]
class Intervention extends Model
{
    /** @use HasFactory<InterventionFactory> */
    use HasFactory;

    public const STATUT_NOUVELLE = 'nouvelle';

    public const STATUT_EN_TRAITEMENT = 'en_traitement';

    public const STATUT_TERMINEE = 'terminee';

    public const STATUTS = [
        self::STATUT_NOUVELLE,
        self::STATUT_EN_TRAITEMENT,
        self::STATUT_TERMINEE,
    ];

    /** Catégories de panne. */
    public const CATEGORIE_MOTEUR = 'moteur';

    public const CATEGORIE_FREINAGE = 'freinage';

    public const CATEGORIE_ELECTRIQUE = 'electrique';

    public const CATEGORIE_TRANSMISSION = 'transmission';

    public const CATEGORIE_AUTRE = 'autre';

    public const CATEGORIES = [
        self::CATEGORIE_MOTEUR,
        self::CATEGORIE_FREINAGE,
        self::CATEGORIE_ELECTRIQUE,
        self::CATEGORIE_TRANSMISSION,
        self::CATEGORIE_AUTRE,
    ];

    /** Niveaux d'urgence. */
    public const URGENCE_FAIBLE = 'faible';

    public const URGENCE_MOYENNE = 'moyenne';

    public const URGENCE_ELEVEE = 'elevee';

    public const URGENCES = [
        self::URGENCE_FAIBLE,
        self::URGENCE_MOYENNE,
        self::URGENCE_ELEVEE,
    ];

    /** Origine de la déclaration. */
    public const SOURCE_AGENCE = 'agence';

    public const SOURCE_CLIENT = 'client';

    protected function casts(): array
    {
        return [
            'date_intervention' => 'date',
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

    public function technicien(): BelongsTo
    {
        return $this->belongsTo(User::class, 'technicien_id');
    }

    public function commentaires(): HasMany
    {
        return $this->hasMany(Commentaire::class);
    }
}
