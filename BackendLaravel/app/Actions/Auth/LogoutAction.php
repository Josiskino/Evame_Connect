<?php

namespace App\Actions\Auth;

use App\Models\User;
use App\Support\TokenManager;

/**
 * Cas d'usage : déconnecter l'utilisateur (révoque le token courant).
 */
final class LogoutAction
{
    public function execute(User $user): void
    {
        TokenManager::revokeCurrent($user);
    }
}
