<?php

namespace App\DTOs\Client\Auth;

/**
 * Données d'inscription d'un nouveau client (après validation OTP).
 * Le téléphone n'est PAS fourni ici : il provient du ticket d'inscription
 * (numéro déjà prouvé par l'OTP).
 */
final readonly class RegisterClientData
{
    public function __construct(
        public string $registrationToken,
        public string $nom,
        public ?string $email,
        public string $ville,
        public string $quartier,
        public ?string $photoUrl,
        public ?string $fcmToken,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            registrationToken: $data['registration_token'],
            nom: $data['nom'],
            email: $data['email'] ?? null,
            ville: $data['ville'],
            quartier: $data['quartier'],
            photoUrl: $data['photo_url'] ?? null,
            fcmToken: isset($data['fcm_token']) && trim((string) $data['fcm_token']) !== '' ? $data['fcm_token'] : null,
        );
    }
}
