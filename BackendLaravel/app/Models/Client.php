<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'nom', 'telephone', 'email', 'adresse',
    'cni_recto', 'cni_verso', 'cni_date_emission', 'cni_date_expiration', 'cni_lieu_emission',
])]
class Client extends Model
{
    /** @use HasFactory<\Database\Factories\ClientFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'cni_date_emission' => 'date',
            'cni_date_expiration' => 'date',
        ];
    }

    public function ventes(): HasMany
    {
        return $this->hasMany(Vente::class);
    }

    public function contrats(): HasMany
    {
        return $this->hasMany(ContratLeasing::class);
    }

    public function interventions(): HasMany
    {
        return $this->hasMany(Intervention::class);
    }
}
