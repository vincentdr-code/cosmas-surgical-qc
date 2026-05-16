<?php

use App\Http\Controllers\ApiController;
use App\Http\Controllers\ReactApiController;

Route::prefix('react')->group(function () {
    Route::get('/health',    [ReactApiController::class, 'health']);
    Route::get('/stats',     [ReactApiController::class, 'stats']);
    Route::get('/audit-log', [ReactApiController::class, 'auditLog']);
    Route::post('/inspect',  [ReactApiController::class, 'inspect']);
    Route::options('/{any}', fn() => response('', 204)
        ->header('Access-Control-Allow-Origin', '*')
        ->header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
        ->header('Access-Control-Allow-Headers', 'Content-Type, Accept')
    )->where('any', '.*');
});

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
        Route::get('/health',      [ApiController::class, 'health'])->name('api.health');
        Route::get('/stats',       [ApiController::class, 'stats'])->name('api.stats');
        Route::get('/inspections', [ApiController::class, 'inspections'])->name('api.inspections');
    });
