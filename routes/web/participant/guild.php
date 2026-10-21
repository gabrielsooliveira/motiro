<?php

use App\Http\Controllers\Participant\GuildController;
use App\Http\Controllers\Participant\TeamController;
use Illuminate\Support\Facades\Route;

/**
 * Portal do Participante — rotas conceituais com prefixo /participante/
 */
Route::middleware(['auth', 'participant.context'])->prefix('participante/guildas')->name('participant.guilds.')->group(function () {
        Route::get('/', [GuildController::class, 'guilds'])->name('index');
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
