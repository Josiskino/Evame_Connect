<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Auth\LoginAction;
use App\Actions\Auth\LogoutAction;
use App\DTOs\Auth\LoginData;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Auth\LoginRequest;
use App\Http\Resources\V1\UserResource;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function login(LoginRequest $request, LoginAction $action): JsonResponse
    {
        $result = $action->execute(LoginData::fromArray($request->validated()));

        // Enregistre le jeton FCM de l'appareil (un utilisateur peut en avoir plusieurs).
        $fcmToken = $request->input('fcm_token');
        if (is_string($fcmToken) && trim($fcmToken) !== '') {
            $result['user']->addFcmToken($fcmToken);
        }

        return ApiResponse::success([
            'token' => $result['token'],
            'user' => new UserResource($result['user']),
        ], 'Connexion réussie.');
    }

    public function me(Request $request): JsonResponse
    {
        return ApiResponse::success(new UserResource($request->user()));
    }

    /** Enregistre un jeton FCM pour l'appareil courant. */
    public function registerFcmToken(Request $request): JsonResponse
    {
        $data = $request->validate(['fcm_token' => ['required', 'string', 'max:255']]);
        $request->user()->addFcmToken($data['fcm_token']);

        return ApiResponse::success(null, 'Jeton enregistré.');
    }

    /** Retire un jeton FCM (déconnexion de l'appareil). */
    public function removeFcmToken(Request $request): JsonResponse
    {
        $data = $request->validate(['fcm_token' => ['required', 'string', 'max:255']]);
        $request->user()->removeFcmToken($data['fcm_token']);

        return ApiResponse::success(null, 'Jeton retiré.');
    }

    public function logout(Request $request, LogoutAction $action): JsonResponse
    {
        $action->execute($request->user());

        return ApiResponse::success(null, 'Déconnexion réussie.');
    }
}
