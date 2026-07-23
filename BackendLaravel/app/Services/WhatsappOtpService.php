<?php

namespace App\Services;

use App\Exceptions\BusinessException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Envoi des codes OTP par WhatsApp via l'API RentXcel.
 * Contrairement au push FCM, l'échec est BLOQUANT : sans code envoyé,
 * l'utilisateur ne peut pas se connecter -> on lève une BusinessException.
 */
class WhatsappOtpService
{
    /**
     * Envoie le code OTP au numéro (format international 228XXXXXXXX).
     *
     * @throws BusinessException si l'envoi échoue.
     */
    public function send(string $phone, string $code, string $locale = 'fr'): void
    {
        $url = (string) config('services.rentxcel.otp_url');
        $secret = (string) config('services.rentxcel.secret');

        try {
            $response = Http::asForm()
                ->timeout(15)
                ->post($url, [
                    'locale' => $locale,
                    'recipient_phone_number' => $phone,
                    'otp_code' => $code,
                    'secret' => $secret,
                ]);
        } catch (\Throwable $e) {
            Log::channel('errors')->error('OTP WhatsApp : requête échouée', [
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);

            throw new BusinessException("Impossible d'envoyer le code de vérification. Veuillez réessayer.", 502);
        }

        if ($response->failed()) {
            Log::channel('errors')->error('OTP WhatsApp : réponse en erreur', [
                'phone' => $phone,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new BusinessException("Impossible d'envoyer le code de vérification. Veuillez réessayer.", 502);
        }
    }
}
