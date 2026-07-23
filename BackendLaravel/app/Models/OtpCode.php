<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * Code OTP de connexion client (téléphone + WhatsApp).
 * Le code n'est jamais stocké en clair (code_hash). Porte aussi le ticket
 * d'inscription (registration_token) pour les nouveaux numéros.
 */
#[Fillable([
    'telephone', 'code_hash', 'expires_at', 'attempts', 'verified_at',
    'registration_token_hash', 'registration_expires_at', 'consumed_at', 'locale',
])]
class OtpCode extends Model
{
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'verified_at' => 'datetime',
            'registration_expires_at' => 'datetime',
            'consumed_at' => 'datetime',
            'attempts' => 'integer',
        ];
    }

    /** Le code est-il encore valide (non expiré, non consommé) ? */
    public function getEstValideAttribute(): bool
    {
        return $this->consumed_at === null
            && $this->expires_at !== null
            && $this->expires_at->isFuture();
    }
}
