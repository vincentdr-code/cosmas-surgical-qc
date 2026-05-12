<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\InspectionController;

Route::get('/', function () {
    return redirect()->route('inspections.upload');
});

// Inspection routes
Route::get('/upload', [InspectionController::class, 'showUploadForm'])->name('inspections.upload');
Route::post('/upload', [InspectionController::class, 'upload'])->name('inspections.store');
Route::get('/results/{inspection}', [InspectionController::class, 'results'])->name('inspections.results');
Route::get('/audit-log', [InspectionController::class, 'auditLog'])->name('inspections.audit-log');
