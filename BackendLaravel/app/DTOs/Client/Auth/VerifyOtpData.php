<?php

namespace App\DTOs\Client\Auth;

use App\Support\PhoneNormalizer;

/**
 * Données d'entrée de la vérification d'un code OTP.
 */
final readonly class VerifyOtpData
{
    public function __construct(
        public string $telephone,   // normalisé (228XXXXXXXX)
        public string $code,
        public ?string $fcmToken,   // jeton push de l'appareil (optionnel)
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            telephone: PhoneNormalizer::toInternational($data['telephone']),
            code: (string) $data['code'],
            fcmToken: isset($data['fcm_token']) && trim((string) $data['fcm_token']) !== '' ? $data['fcm_token'] : null,
        );
    }
}
