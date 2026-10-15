<?php

use App\Http\Controllers\Auth\ContextController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    // Rotas de seleção institucional (amigável e compatível)
    Route::prefix('organizacao')->name('organization.')->group(function () {
        Route::get('/selecionar', [ContextController::class, 'select'])->name('select');
        Route::post('/selecionar', [ContextController::class, 'switch'])->name('select.store');
    });

    Route::prefix('organization')->group(function () {
        Route::get('/selecionar-organizacao', [ContextController::class, 'select']);
        Route::post('/selecionar-organizacao', [ContextController::class, 'switch']);
    });
});