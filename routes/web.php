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
});

Route::middleware(['auth'])->prefix('/optimizations')->group(function () {
    // Operaciones básicas
    Route::get('/', [OptimizationController::class, 'index'])->name('optimizations.index');
    Route::post('/', [OptimizationController::class, 'store'])->name('optimizations.store'); // Ahora hace todo el flujo
    Route::get('/{optimization}', [OptimizationController::class, 'show'])->name('optimizations.show');

    // Solo consulta de estado (ya no hay execute separado)
    Route::get('/{optimization}/status', [OptimizationController::class, 'status'])->name('optimizations.status');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';