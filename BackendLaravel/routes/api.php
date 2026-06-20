<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API EVAME CONNECT
|--------------------------------------------------------------------------
| Toutes les routes sont versionnées. La version courante (V1) est préfixée
| par /api/v1 et définie dans routes/api/v1.php.
*/

Route::prefix('v1')->group(base_path('routes/api/v1.php'));
