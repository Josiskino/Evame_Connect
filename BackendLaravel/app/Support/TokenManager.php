<?php

namespace App\Support;

use App\Models\User;
use Laravel\Sanctum\NewAccessToken;

/**
 * Utilitaire centralisé pour la gestion des tokens d'API (Sanctum).
 * Évite de dupliquer la logique de création/révocation dans les actions.
 */
final class TokenManager
{
    public const DEFAULT_NAME = 'evame-connect';

    /**
     * Émet un nouveau token pour l'utilisateur et retourne sa valeur en clair.
     *
     * @param  array<int, string>  $abilities
     */
    public static function issue(User $user, string $name = self::DEFAULT_NAME, array $abilities = ['*']): string
    {
        return self::create($user, $name, $abilities)->plainTextToken;
    }

    /**
     * Crée un token (objet complet) pour l'utilisateur.
     *
     * @param  array<int, string>  $abilities
     */
    public static function create(User $user, string $name = self::DEFAULT_NAME, array $abilities = ['*']): NewAccessToken
    {
        return $user->createToken($name, $abilities);
    }

    /**
     * Révoque le token courant de l'utilisateur (déconnexion).
     */
    public static function revokeCurrent(User $user): void
    {
        $user->currentAccessToken()?->delete();
    }

    /**
     * Révoque tous les tokens de l'utilisateur (déconnexion globale).
     */
    public static function revokeAll(User $user): void
    {
        $user->tokens()->delete();
    }
}
