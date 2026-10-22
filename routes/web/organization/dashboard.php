<?php

use App\Http\Controllers\Organization\DashboardController;
use Illuminate\Support\Facades\Route;

/**
 * Rotinas de Dashboard e Visão Geral da Organização
 */
Route::middleware(['auth'])->prefix('organization')->name('organization.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);
});
