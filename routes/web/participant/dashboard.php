<?php

use App\Http\Controllers\Participant\DashboardController;
use App\Http\Controllers\Participant\HackathonGroupController;
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
    Route::get('/meus-hackathons', [DashboardController::class, 'myHackathons'])->name('my_hackathons');


    // Equipes (compatibilidade de rotas)
    Route::prefix('equipes')->name('teams.')->group(function () {
        Route::get('/', [DashboardController::class, 'teams'])->name('index');
        Route::post('/', [TeamController::class, 'store'])->name('store');
        Route::get('/{id}', [TeamController::class, 'show'])->name('show');
        Route::post('/{id}/editar', [TeamController::class, 'update'])->name('update');
        Route::delete('/{id}', [TeamController::class, 'destroy'])->name('destroy');
        Route::post('/{id}/sair', [TeamController::class, 'leave'])->name('leave');
        Route::delete('/{id}/membros/{userId}', [TeamController::class, 'removeMember'])->name('members.remove');
        Route::post('/{id}/lider', [TeamController::class, 'electLeader'])->name('leader');
        Route::post('/{id}/convidar', [TeamController::class, 'invite'])->name('invite');
        Route::post('/convites/{id}/reenviar', [TeamController::class, 'resendInvite'])->name('invite.resend');
        Route::delete('/convites/{id}/cancelar', [TeamController::class, 'cancelInvite'])->name('invite.cancel');
        Route::post('/convites/{id}/aceitar', [TeamController::class, 'acceptInvite'])->name('invite.accept');
        Route::post('/convites/{id}/recusar', [TeamController::class, 'declineInvite'])->name('invite.decline');
        Route::post('/{id}/solicitar', [TeamController::class, 'requestToJoin'])->name('request');
        Route::post('/solicitacoes/{id}/aceitar', [TeamController::class, 'acceptRequest'])->name('request.accept');
        Route::post('/solicitacoes/{id}/recusar', [TeamController::class, 'declineRequest'])->name('request.decline');
    });

    // Convites no contexto do participante
    Route::get('/convites/{token}', [TeamController::class, 'showInvitation'])->name('invitations.show');
    Route::post('/convites/{token}/aceitar', [TeamController::class, 'acceptInvitationByToken'])->name('invitations.accept');
    Route::post('/convites/{token}/recusar', [TeamController::class, 'declineInvitationByToken'])->name('invitations.decline');

    // Grupos de Participação em Hackathons (específicos do evento)
    Route::prefix('grupos')->name('groups.')->group(function () {
        Route::get('/', [DashboardController::class, 'groups'])->name('index');
        Route::post('/hackathons/{hackathonId}', [HackathonGroupController::class, 'store'])->name('store');
        Route::post('/hackathons/{hackathonId}/guilda', [HackathonGroupController::class, 'formFromGuild'])->name('form_from_guild');
        Route::post('/{id}/lider', [HackathonGroupController::class, 'electLeader'])->name('leader');
        Route::post('/{id}/sair', [HackathonGroupController::class, 'leave'])->name('leave');
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
    Route::get('/my-hackathons', [DashboardController::class, 'myHackathons'])->name('my_hackathons.explore');

    // Guilds (Domain Oriented)
    Route::prefix('guilds')->name('guild.')->group(function () {
        Route::get('/', [DashboardController::class, 'guilds'])->name('index');
        Route::post('/', [TeamController::class, 'store'])->name('store');
        Route::get('/{id}', [TeamController::class, 'show'])->name('show');
        Route::post('/{id}/update', [TeamController::class, 'update'])->name('update');
        Route::delete('/{id}', [TeamController::class, 'destroy'])->name('destroy');
        Route::post('/{id}/leave', [TeamController::class, 'leave'])->name('leave');
        Route::delete('/{id}/members/{userId}', [TeamController::class, 'removeMember'])->name('members.remove');
        Route::post('/{id}/leader', [TeamController::class, 'electLeader'])->name('leader');
        Route::post('/{id}/invite', [TeamController::class, 'invite'])->name('invite');
        Route::post('/invitations/{id}/resend', [TeamController::class, 'resendInvite'])->name('invite.resend');
        Route::delete('/invitations/{id}/cancel', [TeamController::class, 'cancelInvite'])->name('invite.cancel');
        Route::post('/invitations/{id}/accept', [TeamController::class, 'acceptInvite'])->name('invite.accept');
        Route::post('/invitations/{id}/decline', [TeamController::class, 'declineInvite'])->name('invite.decline');
        Route::post('/{id}/request', [TeamController::class, 'requestToJoin'])->name('request');
        Route::post('/requests/{id}/accept', [TeamController::class, 'acceptRequest'])->name('request.accept');
        Route::post('/requests/{id}/decline', [TeamController::class, 'declineRequest'])->name('request.decline');
    });

    // Teams
    Route::prefix('teams')->name('team.')->group(function () {
        Route::get('/', [DashboardController::class, 'teams'])->name('index');
        Route::post('/', [TeamController::class, 'store'])->name('store');
        Route::get('/{id}', [TeamController::class, 'show'])->name('show');
        Route::post('/{id}/update', [TeamController::class, 'update'])->name('update');
        Route::delete('/{id}', [TeamController::class, 'destroy'])->name('destroy');
        Route::post('/{id}/leave', [TeamController::class, 'leave'])->name('leave');
        Route::delete('/{id}/members/{userId}', [TeamController::class, 'removeMember'])->name('members.remove');
        Route::post('/{id}/leader', [TeamController::class, 'electLeader'])->name('leader');
        Route::post('/{id}/invite', [TeamController::class, 'invite'])->name('invite');
        Route::post('/invitations/{id}/resend', [TeamController::class, 'resendInvite'])->name('invite.resend');
        Route::delete('/invitations/{id}/cancel', [TeamController::class, 'cancelInvite'])->name('invite.cancel');
        Route::post('/invitations/{id}/accept', [TeamController::class, 'acceptInvite'])->name('invite.accept');
        Route::post('/invitations/{id}/decline', [TeamController::class, 'declineInvite'])->name('invite.decline');
        Route::post('/{id}/request', [TeamController::class, 'requestToJoin'])->name('request');
        Route::post('/requests/{id}/accept', [TeamController::class, 'acceptRequest'])->name('request.accept');
        Route::post('/requests/{id}/decline', [TeamController::class, 'declineRequest'])->name('request.decline');
    });

    // Participation Groups
    Route::prefix('groups')->name('group.')->group(function () {
        Route::get('/', [DashboardController::class, 'groups'])->name('index');
        Route::post('/hackathons/{hackathonId}', [HackathonGroupController::class, 'store'])->name('store');
        Route::post('/hackathons/{hackathonId}/guild', [HackathonGroupController::class, 'formFromGuild'])->name('form_from_guild');
        Route::post('/{id}/leader', [HackathonGroupController::class, 'electLeader'])->name('leader');
        Route::post('/{id}/leave', [HackathonGroupController::class, 'leave'])->name('leave');
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
