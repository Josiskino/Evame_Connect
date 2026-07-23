<?php

namespace App\Actions\Client\Auth;

use App\DTOs\Client\Auth\RegisterClientData;
use App\Exceptions\BusinessException;
use App\Models\Client;
use App\Repositories\Contracts\ClientAuthRepositoryInterface;
use App\Repositories\Contracts\OtpRepositoryInterface;
use Illuminate\Support\Carbon;

/**
 * Cas d'usage : créer le compte client après validation OTP (ticket d'inscription).
 * C'est le SEUL point de création d'un client depuis le mobile.
 *
 * @return array{client: Client, token: string}
 */
final class RegisterAction
{
    public function __construct(
        private readonly OtpRepositoryInterface $otps,
        private readonly ClientAuthRepositoryInterface $clients,
    ) {}

    /**
     * @return array{client: Client, token: string}
     */
    public function execute(RegisterClientData $data): array
    {
        $otp = $this->otps->findValidRegistration(hash('sha256', $data->registrationToken));

        if ($otp === null) {
            throw new BusinessException("Session d'inscription invalide ou expirée. Veuillez recommencer.", 422);
        }

        // Le numéro ne doit pas avoir été enregistré entre-temps.
        if ($this->clients->findByPhone($otp->telephone) !== null) {
            throw new BusinessException('Un compte existe déjà pour ce numéro.', 409);
        }

        $client = $this->clients->create([
            'nom' => $data->nom,
            'telephone' => $otp->telephone,   // numéro prouvé par l'OTP
            'email' => $data->email,
            'ville' => $data->ville,
            'quartier' => $data->quartier,
            'photo_url' => $data->photoUrl,
            'source' => Client::SOURCE_MOBILE,
        ]);

        // Consomme le ticket (usage unique).
        $otp->consumed_at = Carbon::now();
        $this->otps->save($otp);

        if ($data->fcmToken !== null) {
            $client->addFcmToken($data->fcmToken);
        }

        return [
            'client' => $client,
            'token' => $client->createToken(Client::TOKEN_NAME)->plainTextToken,
        ];
    }
}
