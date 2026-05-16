<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\InspectionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PipelineController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {

    // ── Inspection routes ───────────────────────────────────────────────────
    Route::get('/upload', [InspectionController::class, 'showUploadForm'])->name('inspections.upload');
    Route::post('/upload', [InspectionController::class, 'upload'])->name('inspections.store');
    Route::get('/results/{inspection}', [InspectionController::class, 'results'])->name('inspections.results');
    Route::get('/audit-log', [InspectionController::class, 'auditLog'])->name('inspections.audit-log');

    // ── ROI Calculator ──────────────────────────────────────────────────────
    Route::get('/roi', [App\Http\Controllers\RoiController::class, 'index'])->name('roi');

    // ── AI Pipeline explainer & Model Stats ────────────────────────────────
    Route::get('/pipeline', [PipelineController::class, 'pipeline'])->name('pipeline');
    Route::get('/model-stats', [PipelineController::class, 'modelStats'])->name('model.stats');

    // ── Profile routes ──────────────────────────────────────────────────────
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
