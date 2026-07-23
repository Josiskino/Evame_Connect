<?php

namespace App\Actions\Client\Auth;

use App\DTOs\Client\Auth\RequestOtpData;
use App\Exceptions\BusinessException;
use App\Repositories\Contracts\OtpRepositoryInterface;
use App\Services\WhatsappOtpService;
use App\Support\OtpGenerator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

/**
 * Cas d'usage : générer un code OTP, le stocker haché et l'envoyer par WhatsApp.
 */
final class RequestOtpAction
{
    private const EXPIRATION_MINUTES = 5;

    private const RATE_LIMIT_MAX = 3;

    private const RATE_LIMIT_WINDOW_MINUTES = 10;

    public function __construct(
        private readonly OtpRepositoryInterface $otps,
        private readonly WhatsappOtpService $whatsapp,
    ) {}

    public function execute(RequestOtpData $data): void
    {
        // Anti-abus : limite le nombre de demandes par numéro sur une fenêtre glissante.
        $recent = $this->otps->countRequestsSince(
            $data->telephone,
            Carbon::now()->subMinutes(self::RATE_LIMIT_WINDOW_MINUTES),
        );

        if ($recent >= self::RATE_LIMIT_MAX) {
            throw new BusinessException('Trop de demandes de code. Veuillez réessayer dans quelques minutes.', 429);
        }

        // Un seul code actif à la fois par numéro.
        $this->otps->invalidateActiveForPhone($data->telephone);

        $code = OtpGenerator::generate();

        $this->otps->create([
            'telephone' => $data->telephone,
            'code_hash' => Hash::make($code),
            'expires_at' => Carbon::now()->addMinutes(self::EXPIRATION_MINUTES),
            'locale' => $data->locale,
        ]);

        // Envoi bloquant : lève une BusinessException si l'API OTP échoue.
        $this->whatsapp->send($data->telephone, $code, $data->locale);
    }
}
