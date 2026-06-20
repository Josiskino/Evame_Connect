<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Format de réponse unifié de l'API EVAME CONNECT.
 *
 * Enveloppe constante : { status, message, data, (meta, links), (errors) }.
 * Les Resources/Collections sont automatiquement « déballées » dans `data`,
 * en préservant la pagination (`meta`, `links`).
 */
final class ApiResponse
{
    /**
     * Réponse de succès.
     */
    public static function success(mixed $data = null, string $message = 'Opération réussie.', int $code = 200): JsonResponse
    {
        $payload = [
            'status' => 'success',
            'message' => $message,
        ];

        if ($data instanceof JsonResource) {
            $resolved = $data->response()->getData(true);
            $payload['data'] = $resolved['data'] ?? $resolved;

            // Préserve la pagination des ResourceCollection
            foreach (['meta', 'links'] as $key) {
                if (isset($resolved[$key])) {
                    $payload[$key] = $resolved[$key];
                }
            }
        } else {
            $payload['data'] = $data;
        }

        return response()->json($payload, $code);
    }

    /**
     * Réponse d'erreur.
     *
     * @param  array<string, mixed>|null  $errors
     */
    public static function error(string $message, int $code = 400, ?array $errors = null): JsonResponse
    {
        $payload = [
            'status' => 'error',
            'message' => $message,
        ];

        if ($errors !== null) {
            $payload['errors'] = $errors;
        }

        return response()->json($payload, $code);
    }
}
