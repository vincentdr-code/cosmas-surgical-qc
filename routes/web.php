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

    // ── Inspection routes ────────────────────────────────────────────────────
    Route::get('/upload',  [InspectionController::class, 'showUploadForm'])->name('inspections.upload');
    Route::post('/upload', [InspectionController::class, 'upload'])->name('inspections.store');
    Route::get('/results/{inspection}',     [InspectionController::class, 'results'])->name('inspections.results');
    Route::get('/results/{inspection}/dhr', [InspectionController::class, 'dhr'])->name('inspections.dhr');
    Route::get('/audit-log',                [InspectionController::class, 'auditLog'])->name('inspections.audit-log');
    Route::get('/audit-log/export-csv',     [InspectionController::class, 'exportCsv'])->name('inspections.export-csv');
    Route::get('/roi', [RoiController::class, 'index'])->name('roi');

    // ── Inspection trace ─────────────────────────────────────────────────────
    Route::get('/inspections/{inspection}/trace', [InspectionController::class, 'trace'])->name('inspections.trace');

    // ── DAMIAN QC Intelligence (NL -> SQL) ───────────────────────────────────
    Route::get('/damian',        [App\Http\Controllers\IntelController::class, 'index'])->name('intel.index');
    Route::post('/damian/query', [App\Http\Controllers\IntelController::class, 'query'])->name('intel.query');

    // ── Settings ─────────────────────────────────────────────────────────────
    Route::get('/settings/threshold',   [App\Http\Controllers\SettingsController::class, 'threshold'])->name('settings.threshold');
    Route::patch('/settings/threshold', [App\Http\Controllers\SettingsController::class, 'updateThreshold'])->name('settings.threshold.update');

    // ── Profile ──────────────────────────────────────────────────────────────
    Route::get('/profile',    [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile',  [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

});

require __DIR__.'/auth.php';

// ── How It Works ─────────────────────────────────────────────────────────────
Route::get('/how-it-works', function () {
    return view('how-it-works');
})->name('how-it-works');

// ── Public ROI Analysis API — called by cosmas-website.vercel.app ─────────────
// No auth required. CORS + CSRF bypass handled in RoiController::analyze().
Route::match(['POST', 'OPTIONS'], '/api/roi-analysis', [RoiController::class, 'analyze'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
