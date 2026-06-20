<?php

namespace App\Support;

use App\Exceptions\BusinessException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Exceptions\UnauthorizedException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

/**
 * Mappe toute exception de l'API vers le format de réponse unifié (ApiResponse)
 * et centralise le logging des erreurs serveur dans le channel « errors ».
 */
final class ApiExceptionHandler
{
    public static function render(Throwable $e, Request $request): JsonResponse
    {
        return match (true) {
            $e instanceof ValidationException => ApiResponse::error(
                'Les données fournies ne sont pas valides.',
                422,
                $e->errors(),
            ),

            $e instanceof AuthenticationException => ApiResponse::error(
                'Authentification requise.',
                401,
            ),

            $e instanceof UnauthorizedException,
            $e instanceof AuthorizationException => ApiResponse::error(
                "Accès non autorisé pour votre profil.",
                403,
            ),

            $e instanceof ModelNotFoundException,
            $e instanceof NotFoundHttpException => ApiResponse::error(
                'Ressource introuvable.',
                404,
            ),

            $e instanceof BusinessException => ApiResponse::error(
                $e->getMessage(),
                $e->getStatusCode(),
                $e->getErrors(),
            ),

            default => self::renderServerError($e, $request),
        };
    }

    private static function renderServerError(Throwable $e, Request $request): JsonResponse
    {
        // Erreur HTTP connue (405, 429, etc.) : pas une vraie erreur serveur
        if ($e instanceof HttpExceptionInterface) {
            return ApiResponse::error(
                $e->getMessage() ?: 'Erreur de requête.',
                $e->getStatusCode(),
            );
        }

        // Vraie erreur serveur : on logge dans le channel centralisé « errors »
        Log::channel('errors')->error($e->getMessage(), [
            'exception' => $e::class,
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'user_id' => $request->user()?->id,
        ]);

        return ApiResponse::error(
            config('app.debug') ? $e->getMessage() : 'Une erreur interne est survenue.',
            500,
        );
    }
}
