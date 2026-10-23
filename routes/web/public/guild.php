<?php

use App\Http\Controllers\Participant\GuildController as ParticipantGuildController;
use App\Http\Controllers\Public\GuildController;
use Illuminate\Support\Facades\Route;

Route::prefix('guilds')->name('guild.')->group(function () {
    // Listagem pública de guilds
    Route::get('/', [GuildController::class, 'index'])->name('index');

    // Convites de guild acessíveis via token público
    Route::prefix('invitations')->name('invitations.')->group(function () {
        Route::get('/{token}', [ParticipantGuildController::class, 'showInvitation'])->name('show');
        Route::post('/{token}/accept', [ParticipantGuildController::class, 'acceptInvitationByToken'])->name('accept');
        Route::post('/{token}/decline', [ParticipantGuildController::class, 'declineInvitationByToken'])->name('decline');
    });

    // Detalhes da guild pública (protegido contra colisões de parâmetros com palavras reservadas)
    Route::get('/{guild}', [GuildController::class, 'show'])->where('guild', '^(?!invitations|convites).*')->name('show');
});
