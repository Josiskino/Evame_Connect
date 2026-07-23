<?php

namespace App\Models;

use Database\Factories\ClientFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable([
    'nom', 'telephone', 'email', 'adresse', 'ville', 'quartier', 'photo_url',
    'points_fidelite', 'source', 'fcm_tokens',
    'cni_recto', 'cni_verso', 'cni_date_emission', 'cni_date_expiration', 'cni_lieu_emission',
])]
#[Hidden(['fcm_tokens'])]
class Client extends Authenticatable
{
    /** @use HasFactory<ClientFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /** Origine du compte client. */
    public const SOURCE_AGENCE = 'agence';

    public const SOURCE_MOBILE = 'mobile';

    /** Nom des jetons Sanctum de l'espace client. */
    public const TOKEN_NAME = 'evame-client';

    protected function casts(): array
    {
        return [
            'cni_date_emission' => 'date',
            'cni_date_expiration' => 'date',
            'points_fidelite' => 'integer',
            'fcm_tokens' => 'array',
        ];
    }

    /**
     * Jetons FCM (un par appareil).
     *
     * @return array<int, string>
     */
    public function fcmTokens(): array
    {
        return $this->fcm_tokens ?? [];
    }

    /** Ajoute un jeton FCM (dédupliqué) — appelé à l'inscription / connexion d'un appareil. */
    public function addFcmToken(string $token): void
    {
        if (trim($token) === '') {
            return;
        }

        $tokens = $this->fcmTokens();
        if (! in_array($token, $tokens, true)) {
            $tokens[] = $token;
            $this->fcm_tokens = $tokens;
            $this->save();
        }
    }

    /** Retire un jeton FCM (déconnexion / appareil retiré). */
    public function removeFcmToken(string $token): void
    {
        $tokens = array_values(array_filter($this->fcmTokens(), fn ($t) => $t !== $token));
        $this->fcm_tokens = $tokens;
        $this->save();
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
