<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['modele', 'couleur', 'cylindree', 'prix', 'image_url', 'stock', 'seuil_alerte'])]
class Moto extends Model
{
    /** @use HasFactory<\Database\Factories\MotoFactory> */
    use HasFactory;

    protected $appends = ['disponible', 'stock_faible'];

    protected function casts(): array
    {
        return [
            'prix' => 'integer',
            'stock' => 'integer',
            'seuil_alerte' => 'integer',
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
