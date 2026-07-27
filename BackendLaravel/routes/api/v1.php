<?php

use App\Http\Controllers\Api\V1\Admin\PermissionController;
use App\Http\Controllers\Api\V1\Admin\RoleController;
use App\Http\Controllers\Api\V1\Admin\UserAccessController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\Client\AuthClientController;
use App\Http\Controllers\Api\V1\Client\CommandeClientController;
use App\Http\Controllers\Api\V1\Client\EntretienClientController;
use App\Http\Controllers\Api\V1\Client\GarageClientController;
use App\Http\Controllers\Api\V1\Client\LeasingClientController;
use App\Http\Controllers\Api\V1\Client\MotoClientController;
use App\Http\Controllers\Api\V1\Client\PanierController;
use App\Http\Controllers\Api\V1\Client\PieceClientController;
use App\Http\Controllers\Api\V1\ClientController;
use App\Http\Controllers\Api\V1\ContratLeasingController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\InterventionController;
use App\Http\Controllers\Api\V1\MotoController;
use App\Http\Controllers\Api\V1\StatsController;
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

    // Statistiques décisionnelles (commercial / SAV)
    Route::get('/stats/commercial', [StatsController::class, 'commercial'])->middleware('permission:'.Permissions::VIEW_DASHBOARD);
    Route::get('/stats/sav', [StatsController::class, 'sav'])->middleware('permission:'.Permissions::VIEW_DASHBOARD);

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
        Route::post('/users', [UserAccessController::class, 'store']);
        Route::post('/users/{user}/permissions', [UserAccessController::class, 'grant']);
        Route::delete('/users/{user}/permissions', [UserAccessController::class, 'revoke']);
        Route::put('/users/{user}/roles', [UserAccessController::class, 'assignRoles']);
    });
});

/*
|--------------------------------------------------------------------------
| Espace client B2C (application mobile)
|--------------------------------------------------------------------------
| Auth dédiée : téléphone + OTP WhatsApp, jetons Sanctum sur le guard `client`.
| Isolé des routes internes ; ne passe pas par Spatie.
*/
Route::prefix('client')->group(function () {

    // --- Public : authentification par OTP ---
    Route::post('/auth/otp/request', [AuthClientController::class, 'requestOtp']);
    Route::post('/auth/otp/verify', [AuthClientController::class, 'verifyOtp']);
    Route::post('/auth/register', [AuthClientController::class, 'register']);

    // --- Protégé (token client) ---
    Route::middleware('auth:client')->group(function () {
        Route::get('/me', [AuthClientController::class, 'me']);
        Route::put('/me', [AuthClientController::class, 'updateProfile']);
        Route::post('/auth/logout', [AuthClientController::class, 'logout']);
        Route::post('/fcm-token', [AuthClientController::class, 'registerFcmToken']);
        Route::delete('/fcm-token', [AuthClientController::class, 'removeFcmToken']);

        // Module 1 — Catalogue moto (lecture)
        Route::get('/motos', [MotoClientController::class, 'index']);
        Route::get('/motos/{moto}', [MotoClientController::class, 'show']);

        // Module 2 — Leasing (simulation + demande)
        Route::post('/leasing/simulate', [LeasingClientController::class, 'simulate']);
        Route::post('/leasing/demandes', [LeasingClientController::class, 'storeDemande']);
        Route::get('/leasing/demandes', [LeasingClientController::class, 'indexDemandes']);
        Route::get('/leasing/demandes/{demande}', [LeasingClientController::class, 'showDemande']);

        // Module 3 — Pièces détachées + panier + commande
        Route::get('/pieces', [PieceClientController::class, 'index']);
        Route::get('/pieces/{piece}', [PieceClientController::class, 'show']);

        Route::get('/panier', [PanierController::class, 'show']);
        Route::post('/panier', [PanierController::class, 'store']);
        Route::put('/panier/lignes/{ligne}', [PanierController::class, 'updateLigne']);
        Route::delete('/panier/lignes/{ligne}', [PanierController::class, 'destroyLigne']);
        Route::delete('/panier', [PanierController::class, 'clear']);

        Route::get('/commandes', [CommandeClientController::class, 'index']);
        Route::post('/commandes', [CommandeClientController::class, 'store']);
        Route::get('/commandes/{commande}', [CommandeClientController::class, 'show']);

        // Module 4 — Mon Garage
        Route::get('/garage', [GarageClientController::class, 'index']);
        Route::get('/garage/contrats/{contrat}', [GarageClientController::class, 'showContrat']);
        Route::get('/garage/paiements', [GarageClientController::class, 'paiements']);
        Route::get('/garage/garanties', [GarageClientController::class, 'garanties']);
        Route::get('/garage/documents', [GarageClientController::class, 'documents']);

        // Module 5 — Entretien (rappels)
        Route::get('/entretiens', [EntretienClientController::class, 'index']);
    });
});
