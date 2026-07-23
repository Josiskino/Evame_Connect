<?php

namespace App\Support;

/**
 * Normalise les numéros de téléphone togolais vers le format international
 * attendu par l'API OTP : `228XXXXXXXX` (indicatif 228 + 8 chiffres, sans « + »).
 */
final class PhoneNormalizer
{
    private const INDICATIF = '228';

    /**
     * Ex. « 90 11 22 33 », « +228 90112233 », « 22890112233 » -> « 22890112233 ».
     */
    public static function toInternational(string $phone): string
    {
        // Ne conserve que les chiffres.
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        // Déjà préfixé par l'indicatif (11 chiffres) : on garde tel quel.
        if (str_starts_with($digits, self::INDICATIF) && strlen($digits) === 11) {
            return $digits;
        }

        // Numéro local à 8 chiffres : on préfixe l'indicatif.
        if (strlen($digits) === 8) {
            return self::INDICATIF.$digits;
        }

        // Autres cas : on retourne les chiffres tels quels (validés en amont).
        return $digits;
    }

    /** Un numéro normalisé valide (indicatif 228 + 8 chiffres). */
    public static function isValid(string $phone): bool
    {
        return (bool) preg_match('/^'.self::INDICATIF.'\d{8}$/', self::toInternational($phone));
    }
}
