<?php

namespace App\Models;

use Database\Factories\MotoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'slug', 'modele', 'marque', 'reference', 'famille', 'classe_cc', 'couleur', 'cylindree', 'puissance', 'couple',
    'prix', 'leasing_eligible', 'image_url', 'images', 'couleurs', 'specifications', 'source_url', 'stock', 'seuil_alerte',
])]
class Moto extends Model
{
    /** @use HasFactory<MotoFactory> */
    use HasFactory;

    protected $appends = ['disponible', 'stock_faible'];

    protected function casts(): array
    {
        return [
            'prix' => 'integer',
            'leasing_eligible' => 'boolean',
            'stock' => 'integer',
            'seuil_alerte' => 'integer',
            'images' => 'array',
            'couleurs' => 'array',
            'specifications' => 'array',
        ];
    }

    /** Moto disponible à la vente. */
    public function getDisponibleAttribute(): bool
    {
        return $this->stock > 0;
    }

    /** Stock faible (alerte). */
    public function getStockFaibleAttribute(): bool
    {
        return $this->stock > 0 && $this->stock <= $this->seuil_alerte;
    }

    public function ventes(): HasMany
    {
        return $this->hasMany(Vente::class);
    }

    public function contrats(): HasMany
    {
        return $this->hasMany(ContratLeasing::class);
    }
}
