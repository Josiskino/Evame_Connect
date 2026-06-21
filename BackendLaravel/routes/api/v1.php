<?php

use App\Http\Controllers\Api\V1\Admin\PermissionController;
use App\Http\Controllers\Api\V1\Admin\RoleController;
use App\Http\Controllers\Api\V1\Admin\UserAccessController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ClientController;
use App\Http\Controllers\Api\V1\ContratLeasingController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\InterventionController;
use App\Http\Controllers\Api\V1\MotoController;
use App\Http\Controllers\Api\V1\VenteController;
use App\Support\Permissions;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API EVAME CONNECT — V1
|--------------------------------------------------------------------------
| Auth : Sanctum (token Bearer). Autorisation : Spatie (permissions par vue/action).
| Le super-admin traverse tout via Gate::before (canAny).
*/

// --- Public --------------------------------------------------------------
Route::post('/login', [AuthController::class, 'login']);

// --- Protégé (token Sanctum) --------------------------------------------
Route::middleware('auth:sanctum')->group(function () {

    // Session / profil
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Jetons FCM (notifications push, multi-appareils)
    Route::post('/me/fcm-token', [AuthController::class, 'registerFcmToken']);
    Route::delete('/me/fcm-token', [AuthController::class, 'removeFcmToken']);

    // Module 2 — Tableau de bord Direction
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->middleware('permission:'.Permissions::VIEW_DASHBOARD);

    // Module 3 — Catalogue motos (lecture, permission uniforme -> apiResource)
    Route::apiResource('motos', MotoController::class)
        ->only(['index', 'show'])
        ->middleware('permission:'.Permissions::VIEW_CATALOGUE);

    // Module 3 — Clients
    Route::get('/clients', [ClientController::class, 'index'])->middleware('permission:'.Permissions::VIEW_CLIENTS);
    Route::get('/clients/stats', [ClientController::class, 'stats'])->middleware('permission:'.Permissions::VIEW_CLIENTS);
    Route::get('/clients/{client}', [ClientController::class, 'show'])->middleware('permission:'.Permissions::VIEW_CLIENTS);
    Route::post('/clients', [ClientController::class, 'store'])->middleware('permission:'.Permissions::CLIENT_CREATE);

    // Module 3 — Ventes
    Route::get('/ventes', [VenteController::class, 'index'])->middleware('permission:'.Permissions::VIEW_VENTES);
    Route::get('/ventes/stats', [VenteController::class, 'stats'])->middleware('permission:'.Permissions::VIEW_VENTES);
    Route::get('/ventes/{vente}', [VenteController::class, 'show'])->middleware('permission:'.Permissions::VIEW_VENTES);
    Route::post('/ventes', [VenteController::class, 'store'])->middleware('permission:'.Permissions::VENTE_CREATE);

    // Module 4 — Leasing
    Route::get('/leasing', [ContratLeasingController::class, 'index'])->middleware('permission:'.Permissions::VIEW_LEASING);
    Route::get('/leasing/stats', [ContratLeasingController::class, 'stats'])->middleware('permission:'.Permissions::VIEW_LEASING);
    Route::post('/leasing/simulate', [ContratLeasingController::class, 'simulate'])->middleware('permission:'.Permissions::VIEW_LEASING);
    Route::get('/leasing/{leasing}', [ContratLeasingController::class, 'show'])->middleware('permission:'.Permissions::VIEW_LEASING);
    Route::post('/leasing', [ContratLeasingController::class, 'store'])->middleware('permission:'.Permissions::LEASING_CREATE);
    Route::post('/leasing/{leasing}/paiements', [ContratLeasingController::class, 'storePaiement'])->middleware('permission:'.Permissions::PAIEMENT_CREATE);

    // Module 5 — SAV / interventions
    Route::get('/interventions', [InterventionController::class, 'index'])->middleware('permission:'.Permissions::VIEW_INTERVENTIONS);
    Route::get('/technicians', [InterventionController::class, 'technicians'])->middleware('permission:'.Permissions::VIEW_INTERVENTIONS);
    Route::get('/interventions/{intervention}', [InterventionController::class, 'show'])->middleware('permission:'.Permissions::VIEW_INTERVENTIONS);
    Route::post('/interventions', [InterventionController::class, 'store'])->middleware('permission:'.Permissions::INTERVENTION_CREATE);
    Route::match(['put', 'patch'], '/interventions/{intervention}', [InterventionController::class, 'update'])->middleware('permission:'.Permissions::INTERVENTION_UPDATE);
    Route::post('/interventions/{intervention}/commentaires', [InterventionController::class, 'storeCommentaire'])->middleware('permission:'.Permissions::INTERVENTION_UPDATE);

    // Administration RBAC (super-admin via Gate::before, ou permission rbac.manage)
    Route::prefix('admin')->middleware('permission:'.Permissions::RBAC_MANAGE)->group(function () {
        Route::get('/permissions', [PermissionController::class, 'index']);

        Route::apiResource('roles', RoleController::class)->only(['index', 'store', 'destroy']);
        Route::put('/roles/{role}/permissions', [RoleController::class, 'updatePermissions']);

        Route::get('/users', [UserAccessController::class, 'index']);
        Route::post('/users/{user}/permissions', [UserAccessController::class, 'grant']);
        Route::delete('/users/{user}/permissions', [UserAccessController::class, 'revoke']);
        Route::put('/users/{user}/roles', [UserAccessController::class, 'assignRoles']);
    });
});
