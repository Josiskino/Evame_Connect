<?php

namespace App\Actions\Client\Auth;

use App\DTOs\Client\Auth\UpdateClientProfileData;
use App\Models\Client;

/**
 * Cas d'usage : mettre à jour le profil du client (« Modification éventuelle »).
 */
final class UpdateProfileAction
{
    public function execute(Client $client, UpdateClientProfileData $data): Client
    {
        $updates = $data->toUpdateArray();

        if ($updates !== []) {
            $client->update($updates);
        }

        return $client->refresh();
    }
}
