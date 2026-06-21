<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Canaux de diffusion (Broadcast)
|--------------------------------------------------------------------------
| Canal privé propre à chaque utilisateur : il ne peut écouter QUE le sien.
| Utilisé pour pousser en temps réel la mise à jour de ses accès/vues.
*/

Broadcast::channel('user.{id}', function (User $user, int $id) {
    return (int) $user->id === (int) $id;
});

/*
| Canal d'activité partagé : tout utilisateur authentifié peut l'écouter pour
| recevoir, en temps réel, les créations/modifications/suppressions de ressources.
*/
Broadcast::channel('evame.activity', function (User $user) {
    return $user !== null;
});
