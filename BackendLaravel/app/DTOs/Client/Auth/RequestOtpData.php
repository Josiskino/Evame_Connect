<?php

namespace App\DTOs\Client\Auth;

use App\Support\PhoneNormalizer;

/**
 * Données d'entrée d'une demande de code OTP.
 */
final readonly class RequestOtpData
{
    public function __construct(
        public string $telephone,   // normalisé (228XXXXXXXX)
        public string $locale,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            telephone: PhoneNormalizer::toInternational($data['telephone']),
            locale: in_array($data['locale'] ?? 'fr', ['fr', 'en'], true) ? $data['locale'] : 'fr',
        );
    }
}
