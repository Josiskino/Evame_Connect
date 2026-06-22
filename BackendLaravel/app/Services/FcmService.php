<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

/**
 * Envoi de notifications push FCM (Firebase Cloud Messaging, API HTTP v1)
 * vers les appareils d'un utilisateur. Échec silencieux + journalisé : une
 * panne FCM ne doit jamais casser la requête métier.
 */
class FcmService
{
    /**
     * Envoie une notification push à tous les appareils de l'utilisateur.
     *
     * @param  array<string, string>  $data
     */
    public function sendToUser(User $user, string $title, string $body, array $data = []): void
    {
        $tokens = $user->fcmTokens();
        if (empty($tokens)) {
            return;
        }

        try {
            /** @var \Kreait\Firebase\Contract\Messaging $messaging */
            $messaging = app('firebase.messaging');

            $message = CloudMessage::new()
                ->withNotification(Notification::create($title, $body))
                ->withData(array_map(static fn ($v) => (string) $v, $data));

            $report = $messaging->sendMulticast($message, $tokens);

            // Purge les jetons devenus invalides (appareils désinstallés, etc.).
            foreach (array_merge($report->invalidTokens(), $report->unknownTokens()) as $bad) {
                $user->removeFcmToken($bad);
            }
        } catch (\Throwable $e) {
            Log::channel('errors')->warning('FCM: envoi échoué', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
