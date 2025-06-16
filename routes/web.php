<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OptimizationController;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    // Ruta principal del dashboard que redirige a inicio
    Route::get('/dashboard', function () {
        return redirect()->route('dashboard.inicio');
    })->name('dashboard');

    // Rutas del dashboard modularizado
    Route::get('/dashboard/inicio', [OptimizationController::class, 'inicio'])->name('dashboard.inicio');
    Route::get('/dashboard/historial', [OptimizationController::class, 'historial'])->name('dashboard.historial');
    Route::get('/dashboard/resultados', [OptimizationController::class, 'resultados'])->name('dashboard.resultados');

    // Rutas adicionales que puedas necesitar
    Route::post('/dashboard/optimize', [OptimizationController::class, 'optimize'])->name('dashboard.optimize');
});

Route::middleware(['auth'])->prefix('/optimizations')->group(function () {
    // CRUD básico
    Route::get('/', [OptimizationController::class, 'index'])->name('optimizations.index');
    Route::post('/', [OptimizationController::class, 'store'])->name('optimizations.store');
    Route::get('/{optimization}', [OptimizationController::class, 'show'])->name('optimizations.show');
    Route::delete('/{optimization}', [OptimizationController::class, 'destroy'])->name('optimizations.destroy');

    // Ejecución y monitoreo
    Route::post('/{optimization}/execute', [OptimizationController::class, 'execute'])->name('optimizations.execute');
    Route::get('/{optimization}/status', [OptimizationController::class, 'status'])->name('optimizations.status');
    Route::post('/{optimization}/cancel', [OptimizationController::class, 'cancel'])->name('optimizations.cancel');
    Route::get('/{optimization}/logs', [OptimizationController::class, 'logs'])->name('optimizations.logs');

    // Archivos y datos
    Route::get('/{optimization}/preview', [OptimizationController::class, 'preview'])->name('optimizations.preview');
    Route::get('/{optimization}/download-inputs', [OptimizationController::class, 'downloadInputFiles'])->name('optimizations.download-inputs');
    Route::get('/{optimization}/download-results', [OptimizationController::class, 'downloadResults'])->name('optimizations.download-results');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
