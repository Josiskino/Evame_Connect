<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API EVAME CONNECT
|--------------------------------------------------------------------------
| Toutes les routes sont versionnées. La version courante (V1) est préfixée
| par /api/v1 et définie dans routes/api/v1.php.
*/

// Authentification des canaux de diffusion privés (Pusher) via token Sanctum.
// Appelé par Laravel Echo : POST /api/broadcasting/auth (Authorization: Bearer ...).
Route::post('/broadcasting/auth', fn (Request $request) => Broadcast::auth($request))
    ->middleware('auth:sanctum');

Route::prefix('v1')->group(base_path('routes/api/v1.php'));
