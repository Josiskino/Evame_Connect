<?php

namespace App\Actions\Client\Auth;

use App\Models\Client;

/**
 * Cas d'usage : déconnecter le client (révoque le token courant).
 */
final class LogoutAction
{
    public function execute(Client $client): void
    {
        $client->currentAccessToken()?->delete();
    }
}
