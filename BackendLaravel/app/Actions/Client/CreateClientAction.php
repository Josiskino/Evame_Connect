<?php

namespace App\Actions\Client;

use App\DTOs\Client\CreateClientData;
use App\Models\Client;
use App\Repositories\Contracts\ClientRepositoryInterface;
use App\Support\PhoneNormalizer;

final class CreateClientAction
{
    public function __construct(
        private readonly ClientRepositoryInterface $clients,
    ) {}

    public function execute(CreateClientData $data): Client
    {
        $payload = $data->toArray();

        // Téléphone stocké au format normalisé (228XXXXXXXX) : indispensable pour
        // que le client créé en agence puisse se connecter via l'app (login OTP).
        if (! empty($payload['telephone'])) {
            $payload['telephone'] = PhoneNormalizer::toInternational($payload['telephone']);
        }

        return $this->clients->create($payload);
    }
}
