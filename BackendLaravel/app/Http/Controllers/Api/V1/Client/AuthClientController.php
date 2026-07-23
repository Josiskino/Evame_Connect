<?php

namespace App\Http\Controllers\Api\V1\Client;

use App\Actions\Client\Auth\LogoutAction;
use App\Actions\Client\Auth\RegisterAction;
use App\Actions\Client\Auth\RequestOtpAction;
use App\Actions\Client\Auth\UpdateProfileAction;
use App\Actions\Client\Auth\VerifyOtpAction;
use App\DTOs\Client\Auth\RegisterClientData;
use App\DTOs\Client\Auth\RequestOtpData;
use App\DTOs\Client\Auth\UpdateClientProfileData;
use App\DTOs\Client\Auth\VerifyOtpData;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Client\Auth\RegisterRequest;
use App\Http\Requests\V1\Client\Auth\RequestOtpRequest;
use App\Http\Requests\V1\Client\Auth\UpdateProfileRequest;
use App\Http\Requests\V1\Client\Auth\VerifyOtpRequest;
use App\Http\Resources\V1\Client\ClientProfileResource;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthClientController extends Controller
{
    /** Étape 1 : demande d'un code OTP par WhatsApp. */
    public function requestOtp(RequestOtpRequest $request, RequestOtpAction $action): JsonResponse
    {
        $action->execute(RequestOtpData::fromArray($request->validated()));

        return ApiResponse::success(null, 'Un code de vérification vous a été envoyé par WhatsApp.');
    }

    /** Étape 2 : vérification du code -> connexion (client connu) ou ticket d'inscription (nouveau). */
    public function verifyOtp(VerifyOtpRequest $request, VerifyOtpAction $action): JsonResponse
    {
        $result = $action->execute(VerifyOtpData::fromArray($request->validated()));

        if ($result['is_new_user']) {
            return ApiResponse::success([
                'is_new_user' => true,
                'registration_token' => $result['registration_token'],
            ], 'Numéro vérifié. Veuillez compléter votre inscription.');
        }

        return ApiResponse::success([
            'is_new_user' => false,
            'token' => $result['token'],
            'client' => new ClientProfileResource($result['client']),
        ], 'Connexion réussie.');
    }

    /** Étape 3 (nouveau) : création du compte après validation OTP. */
    public function register(RegisterRequest $request, RegisterAction $action): JsonResponse
    {
        $data = $request->validated();

        if ($request->hasFile('photo')) {
            $data['photo_url'] = $request->file('photo')->store('clients', 'public');
        }

        $result = $action->execute(RegisterClientData::fromArray($data));

        return ApiResponse::success([
            'token' => $result['token'],
            'client' => new ClientProfileResource($result['client']),
        ], 'Compte créé.', 201);
    }

    /** Profil du client connecté. */
    public function me(Request $request): JsonResponse
    {
        return ApiResponse::success(new ClientProfileResource($request->user()));
    }

    /** Modification du profil (« Modification éventuelle »). */
    public function updateProfile(UpdateProfileRequest $request, UpdateProfileAction $action): JsonResponse
    {
        $data = $request->validated();

        if ($request->hasFile('photo')) {
            $data['photo_url'] = $request->file('photo')->store('clients', 'public');
        }

        $client = $action->execute($request->user(), UpdateClientProfileData::fromArray($data));

        return ApiResponse::success(new ClientProfileResource($client), 'Profil mis à jour.');
    }

    /** Enregistre un jeton FCM pour l'appareil courant (multi-device). */
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
