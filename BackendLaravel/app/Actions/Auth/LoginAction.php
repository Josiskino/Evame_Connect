<?php

namespace App\Actions\Auth;

use App\DTOs\Auth\LoginData;
use App\Exceptions\BusinessException;
use App\Models\User;
use App\Support\TokenManager;
use Illuminate\Support\Facades\Auth;

/**
 * Cas d'usage : authentifier un utilisateur et émettre un token.
 *
 * @return array{user: User, token: string}
 */
final class LoginAction
{
    public function execute(LoginData $data): array
    {
        if (! Auth::attempt(['email' => $data->email, 'password' => $data->password])) {
            throw new BusinessException('Identifiants incorrects.', 401);
        }

        /** @var User $user */
        $user = Auth::user();

        return [
            'user' => $user,
            'token' => TokenManager::issue($user),
        ];
    }
}
