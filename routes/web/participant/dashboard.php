<?php

use App\Http\Controllers\Participant\DashboardController;
use App\Http\Controllers\Participant\ProfileController;
use Illuminate\Support\Facades\Route;

// Fluxo para ativar/completar o perfil de participante (qualquer usuário autenticado)
Route::middleware(['auth'])->prefix('participant')->name('participant.')->group(function () {
    Route::get('/completar-perfil', [ProfileController::class, 'create'])->name('complete_profile');
    Route::post('/completar-perfil', [ProfileController::class, 'store'])->name('complete_profile.store');
});

// Alias amigável em português para completar perfil
Route::middleware(['auth'])->get('/participante/completar-perfil', [ProfileController::class, 'create']);

// Rotas protegidas por autenticação e contexto de participante
Route::middleware(['auth', 'participant.context'])->prefix('participant')->name('participant.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/', [DashboardController::class, 'index'])->name('index');
});

// Aliases amigáveis em português para o dashboard do participante
Route::middleware(['auth', 'participant.context'])->get('/participante/dashboard', [DashboardController::class, 'index']);
Route::middleware(['auth', 'participant.context'])->get('/participante', [DashboardController::class, 'index']);
