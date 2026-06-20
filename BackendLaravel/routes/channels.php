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
