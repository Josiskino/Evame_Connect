<?php

namespace App\Support;

/**
 * Génère des codes OTP numériques cryptographiquement sûrs.
 */
final class OtpGenerator
{
    public const LENGTH = 6;

    /**
     * Retourne un code à 6 chiffres (avec zéros de tête conservés).
     */
    public static function generate(int $length = self::LENGTH): string
    {
        $max = (10 ** $length) - 1;

        return str_pad((string) random_int(0, $max), $length, '0', STR_PAD_LEFT);
    }
}
