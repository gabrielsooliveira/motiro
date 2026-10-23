<?php

use App\Http\Controllers\Participant\HackathonController;
use Illuminate\Support\Facades\Route;

// Rotas de exploração de hackathons pelo participante
Route::middleware(['auth', 'participant.context'])->prefix('participant')->name('participant.')->group(function () {
    Route::get('/hackathons', [HackathonController::class, 'index'])->name('hackathons');
    Route::get('/hackathons/explore', [HackathonController::class, 'index'])->name('hackathon.index');
});
