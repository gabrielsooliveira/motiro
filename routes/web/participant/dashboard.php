<?php

use App\Http\Controllers\Participant\DashboardController;
use App\Http\Controllers\Participant\ProjectController;
use App\Http\Controllers\Participant\RegistrationController;
use App\Http\Controllers\Participant\TeamController;
use Illuminate\Support\Facades\Route;

/**
 * Portal do Participante — rotas conceituais com prefixo /participante/
 */
Route::middleware(['auth', 'participant.context'])->prefix('participante')->name('participant.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/', [DashboardController::class, 'index'])->name('index');
    Route::get('/hackathons', [DashboardController::class, 'hackathons'])->name('hackathons');

    // Equipes (amigável)
    Route::prefix('equipes')->name('teams.')->group(function () {
        Route::post('/', [TeamController::class, 'store'])->name('store');
        Route::get('/{id}', [TeamController::class, 'show'])->name('show');
        Route::post('/{id}/convidar', [TeamController::class, 'invite'])->name('invite');
        Route::post('/convites/{id}/aceitar', [TeamController::class, 'acceptInvite'])->name('invite.accept');
        Route::post('/convites/{id}/recusar', [TeamController::class, 'declineInvite'])->name('invite.decline');
        Route::post('/{id}/solicitar', [TeamController::class, 'requestToJoin'])->name('request');
        Route::post('/solicitacoes/{id}/aceitar', [TeamController::class, 'acceptRequest'])->name('request.accept');
        Route::post('/solicitacoes/{id}/recusar', [TeamController::class, 'declineRequest'])->name('request.decline');
    });

    // Inscrições (amigável)
    Route::prefix('inscricoes')->name('registrations.')->group(function () {
        Route::post('/', [RegistrationController::class, 'store'])->name('store');
        Route::delete('/{id}', [RegistrationController::class, 'cancel'])->name('cancel');
    });

    // Projetos (amigável)
    Route::prefix('projetos')->name('projects.')->group(function () {
        Route::get('/{id}', [ProjectController::class, 'show'])->name('show');
        Route::post('/enviar', [ProjectController::class, 'submit'])->name('submit');
    });
});

/**
 * Rotas padrão / legadas do participante (compatíveis com componentes Vue e testes existentes)
 */
Route::middleware(['auth'])->prefix('dashboard')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard.index');
    Route::get('/hackathons', [DashboardController::class, 'hackathons'])->name('hackathons.explore');

    // Teams
    Route::prefix('teams')->name('team.')->group(function () {
        Route::post('/', [TeamController::class, 'store'])->name('store');
        Route::get('/{id}', [TeamController::class, 'show'])->name('show');
        Route::post('/{id}/invite', [TeamController::class, 'invite'])->name('invite');
        Route::post('/invitations/{id}/accept', [TeamController::class, 'acceptInvite'])->name('invite.accept');
        Route::post('/invitations/{id}/decline', [TeamController::class, 'declineInvite'])->name('invite.decline');
        Route::post('/{id}/request', [TeamController::class, 'requestToJoin'])->name('request');
        Route::post('/requests/{id}/accept', [TeamController::class, 'acceptRequest'])->name('request.accept');
        Route::post('/requests/{id}/decline', [TeamController::class, 'declineRequest'])->name('request.decline');
    });

    // Registrations
    Route::prefix('registrations')->name('registration.')->group(function () {
        Route::post('/', [RegistrationController::class, 'store'])->name('store');
        Route::patch('/{id}/status', [RegistrationController::class, 'updateStatus'])->name('status.update');
        Route::delete('/{id}', [RegistrationController::class, 'cancel'])->name('cancel');
    });

    // Projects
    Route::prefix('projects')->name('project.')->group(function () {
        Route::get('/{id}', [ProjectController::class, 'show'])->name('show');
        Route::post('/submit', [ProjectController::class, 'submit'])->name('submit');
    });
});
