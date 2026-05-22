<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\InspectionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\RoiController;
use Illuminate\Support\Facades\Route;

// ── Root redirect ────────────────────────────────────────────────────────────
// Authenticated users hitting / go to /home; guests go to /login.
Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('home')
        : redirect()->route('login');
});

// ── Stale / guessable URL redirects (eliminate 404 root causes) ─────────────
// /inspect was never a valid route; /upload is correct.
Route::get('/inspect', function () {
    return redirect()->route('inspections.upload', [], 301);
})->middleware(['auth']);
// /inspections/{id} mirrors the old Laravel resource-style URL; /results/{id} is correct.
Route::get('/inspections/{id}', function ($id) {
    return redirect()->route('inspections.results', $id, 301);
})->middleware(['auth']);

Route::get('/home', function () {
    return view('welcome');
})->middleware(['auth'])->name('home');

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    // Inspection routes
    Route::get('/upload', [InspectionController::class, 'showUploadForm'])->name('inspections.upload');
    Route::post('/upload', [InspectionController::class, 'upload'])->name('inspections.store');
    Route::get('/results/{inspection}', [InspectionController::class, 'results'])->name('inspections.results');
    Route::get('/audit-log', [InspectionController::class, 'auditLog'])->name('inspections.audit-log');
    Route::get('/roi', [RoiController::class, 'index'])->name('roi');

    // Profile routes
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
