<?php

use App\Http\Controllers\ApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — Cosmas Sentry
|--------------------------------------------------------------------------
| Public aggregate endpoints (no PII). Auth-protected endpoints use
| sanctum middleware and are prefixed /api/v1/secure/.
*/

Route::prefix('v1')
    ->middleware(['throttle:60,1'])  // 60 requests per minute per IP
    ->group(function () {
        Route::get('/stats',       [ApiController::class, 'stats'])->name('api.stats');
        Route::get('/inspections', [ApiController::class, 'inspections'])->name('api.inspections');
    });
