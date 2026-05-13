<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\InspectionController;

Route::prefix('inspections')->group(function () {
    // Upload and analyze image
    Route::post('/', [InspectionController::class, 'store'])->name('api.inspections.store');
    
    // Get specific inspection
    Route::get('/{inspection}', [InspectionController::class, 'show'])->name('api.inspections.show');
    
    // List all inspections with filters and pagination
    Route::get('/', [InspectionController::class, 'index'])->name('api.inspections.index');
});

// Health check
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toIso8601String(),
        'service' => 'Cosmas API v1.0',
    ]);
});
