<?php

use App\Http\Controllers\Participant\DashboardController;
use App\Http\Controllers\Organization\HackathonController;
use App\Http\Controllers\Participant\ProjectController;
use App\Http\Controllers\Participant\RegistrationController;
use App\Http\Controllers\Participant\TeamController;
use Illuminate\Support\Facades\Route;

/**
 * Portal do Participante — rotas com prefixo /participante/
 * Espelha as rotas de /dashboard/ com URLs amigáveis por contexto.
 */
Route::middleware(['auth'])->prefix('participante')->name('participant.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Hackathons públicos (alias contextual)
    Route::get('/hackathons', [HackathonController::class, 'index'])->name('hackathons');
    Route::get('/hackathons/{slug}', [HackathonController::class, 'show'])->name('hackathons.show');

    // Inscrições
    Route::prefix('inscricoes')->name('registrations.')->group(function () {
        Route::post('/', [RegistrationController::class, 'store'])->name('store');
    });

    // Equipes
    Route::prefix('equipes')->name('teams.')->group(function () {
        Route::post('/', [TeamController::class, 'store'])->name('store');
        Route::get('/{id}', [TeamController::class, 'show'])->name('show');
        Route::post('/{id}/convidar', [TeamController::class, 'invite'])->name('invite');
        Route::post('/convites/{id}/aceitar', [TeamController::class, 'acceptInvite'])->name('invite.accept');
        Route::post('/convites/{id}/recusar', [TeamController::class, 'declineInvite'])->name('invite.decline');
    });

    // Projetos
    Route::prefix('projetos')->name('projects.')->group(function () {
        Route::get('/{id}', [ProjectController::class, 'show'])->name('show');
        Route::post('/enviar', [ProjectController::class, 'submit'])->name('submit');
    });
});
