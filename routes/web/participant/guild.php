<?php

use App\Http\Controllers\Participant\GuildController;
use Illuminate\Support\Facades\Route;

// Rotas do participante para gerenciamento de suas Guilds
Route::middleware(['auth', 'participant.context'])->prefix('participant/guilds')->name('participant.guilds.')->group(function () {
    Route::get('/', [GuildController::class, 'index'])->name('index');
    Route::post('/', [GuildController::class, 'store'])->name('store');
    Route::get('/{id}', [GuildController::class, 'show'])->name('show');
    Route::put('/{id}', [GuildController::class, 'update'])->name('update');
    Route::post('/{id}/update', [GuildController::class, 'update'])->name('update.post');
    Route::delete('/{id}', [GuildController::class, 'destroy'])->name('destroy');
    Route::delete('/{id}/leave', [GuildController::class, 'leave'])->name('leave');

    // Membros e liderança
    Route::delete('/{id}/members/{userId}', [GuildController::class, 'removeMember'])->name('members.remove');
    Route::post('/{id}/elect-leader', [GuildController::class, 'electLeader'])->name('electLeader');

    // Convites e solicitações diretas no painel
    Route::post('/{id}/invite', [GuildController::class, 'invite'])->name('invite');
    Route::post('/{id}/request', [GuildController::class, 'requestToJoin'])->name('request');
    Route::post('/invitations/{id}/resend', [GuildController::class, 'resendInvite'])->name('invite.resend');
    Route::delete('/invitations/{id}/cancel', [GuildController::class, 'cancelInvite'])->name('invite.cancel');
    Route::post('/invitations/{id}/accept', [GuildController::class, 'acceptInvite'])->name('invite.accept');
    Route::post('/invitations/{id}/decline', [GuildController::class, 'declineInvite'])->name('invite.decline');

    // Moderação e bloqueio de membros
    Route::post('/{id}/members/{userId}/block', [GuildController::class, 'blockMember'])->name('blockMember');
    Route::delete('/{id}/members/{userId}/unblock', [GuildController::class, 'unblockMember'])->name('unblockMember');
    Route::get('/{id}/blocked-members', [GuildController::class, 'getBlockedMembers'])->name('blockedMembers');
});
