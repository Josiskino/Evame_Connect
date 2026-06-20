<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['client_id', 'moto_id', 'technicien_id', 'probleme', 'statut', 'date_intervention'])]
class Intervention extends Model
{
    /** @use HasFactory<\Database\Factories\InterventionFactory> */
    use HasFactory;

    public const STATUT_NOUVELLE = 'nouvelle';
    public const STATUT_EN_TRAITEMENT = 'en_traitement';
    public const STATUT_TERMINEE = 'terminee';

    public const STATUTS = [
        self::STATUT_NOUVELLE,
        self::STATUT_EN_TRAITEMENT,
        self::STATUT_TERMINEE,
    ];

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
