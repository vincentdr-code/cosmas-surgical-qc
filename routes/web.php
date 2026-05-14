<?php
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\InspectionController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('inspections.upload');
});

Route::get('/dashboard', function () {
    return redirect()->route('inspections.upload');
})->middleware(['auth'])->name('dashboard');

Route::middleware('auth')->group(function () {
    // Inspection routes
    Route::get('/upload', [InspectionController::class, 'showUploadForm'])->name('inspections.upload');
    Route::post('/upload', [InspectionController::class, 'upload'])->name('inspections.store');
    Route::get('/results/{inspection}', [InspectionController::class, 'results'])->name('inspections.results');
    Route::get('/audit-log', [InspectionController::class, 'auditLog'])->name('inspections.audit-log');

    // Profile routes
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
