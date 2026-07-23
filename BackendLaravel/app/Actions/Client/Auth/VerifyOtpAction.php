<?php

namespace App\Actions\Client\Auth;

use App\DTOs\Client\Auth\VerifyOtpData;
use App\Exceptions\BusinessException;
use App\Models\Client;
use App\Repositories\Contracts\ClientAuthRepositoryInterface;
use App\Repositories\Contracts\OtpRepositoryInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Cas d'usage : vérifier un code OTP.
 *  - numéro connu  -> connexion (token émis) ;
 *  - numéro inconnu -> aucun compte créé, ticket d'inscription retourné.
 *
 * @phpstan-type VerifyResult array{is_new_user: bool, token?: string, client?: Client, registration_token?: string}
 */
final class VerifyOtpAction
{
    private const MAX_ATTEMPTS = 5;

    private const REGISTRATION_MINUTES = 15;

    public function __construct(
        private readonly OtpRepositoryInterface $otps,
        private readonly ClientAuthRepositoryInterface $clients,
    ) {}

    /**
     * @return array{is_new_user: bool, token?: string, client?: Client, registration_token?: string}
     */
    public function execute(VerifyOtpData $data): array
    {
        $otp = $this->otps->findLatestActiveByPhone($data->telephone);

        if ($otp === null) {
            throw new BusinessException('Code invalide ou expiré. Veuillez en demander un nouveau.', 422);
        }

        if ($otp->attempts >= self::MAX_ATTEMPTS) {
            $otp->consumed_at = Carbon::now();
            $this->otps->save($otp);

            throw new BusinessException('Trop de tentatives. Veuillez demander un nouveau code.', 429);
        }

        if (! Hash::check($data->code, $otp->code_hash)) {
            $otp->attempts++;
            $this->otps->save($otp);

            throw new BusinessException('Code incorrect.', 422);
        }

        // Code validé.
        $otp->verified_at = Carbon::now();

        $client = $this->clients->findByPhone($data->telephone);

        if ($client !== null) {
            // Client existant -> connexion immédiate.
            $otp->consumed_at = Carbon::now();
            $this->otps->save($otp);

            if ($data->fcmToken !== null) {
                $client->addFcmToken($data->fcmToken);
            }

            return [
                'is_new_user' => false,
                'token' => $client->createToken(Client::TOKEN_NAME)->plainTextToken,
                'client' => $client,
            ];
        }

        // Nouveau numéro -> ticket d'inscription, AUCUN client créé ici.
        $registrationToken = Str::random(48);
        $otp->registration_token_hash = hash('sha256', $registrationToken);
        $otp->registration_expires_at = Carbon::now()->addMinutes(self::REGISTRATION_MINUTES);
        $this->otps->save($otp);

        return [
            'is_new_user' => true,
            'registration_token' => $registrationToken,
        ];
    }
}
