<?php

use App\Http\Controllers\Participant\DashboardController;
use Illuminate\Support\Facades\Route;

/**
 * Rotas de Dashboard e Exploração de Hackathons do Participante
 */
Route::middleware(['auth'])->prefix('participante')->name('participant.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/', [DashboardController::class, 'index'])->name('index');
});
