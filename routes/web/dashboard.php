<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HackathonController;
use App\Http\Controllers\JudgeController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\TeamController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->prefix('dashboard')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard.index');

    // Teams
    Route::prefix('teams')->name('team.')->group(function () {
        Route::post('/', [TeamController::class, 'store'])->name('store');
        Route::get('/{id}', [TeamController::class, 'show'])->name('show');
        Route::post('/{id}/invite', [TeamController::class, 'invite'])->name('invite');
        Route::post('/invitations/{id}/accept', [TeamController::class, 'acceptInvite'])->name('invite.accept');
        Route::post('/invitations/{id}/decline', [TeamController::class, 'declineInvite'])->name('invite.decline');
    });

    // Registrations
    Route::prefix('registrations')->name('registration.')->group(function () {
        Route::post('/', [RegistrationController::class, 'store'])->name('store');
        Route::patch('/{id}/status', [RegistrationController::class, 'updateStatus'])->name('status.update');
    });

    // Projects
    Route::prefix('projects')->name('project.')->group(function () {
        Route::get('/{id}', [ProjectController::class, 'show'])->name('show');
        Route::post('/submit', [ProjectController::class, 'submit'])->name('submit');
    });

    // Judging
    Route::prefix('judging')->name('judge.')->group(function () {
        Route::get('/', [JudgeController::class, 'index'])->name('index');
        Route::get('/project/{id}', [JudgeController::class, 'evaluate'])->name('evaluate');
        Route::post('/project/{id}/evaluate', [JudgeController::class, 'storeEvaluation'])->name('evaluate.store');
        Route::post('/conflict', [JudgeController::class, 'declareConflict'])->name('conflict.declare');
    });

    // Admin Hackathon Management
    Route::prefix('hackathons')->name('hackathon.manage.')->group(function () {
        Route::get('/{id}/manage', [HackathonController::class, 'manage'])->name('index');
        Route::post('/', [HackathonController::class, 'store'])->name('store');
        Route::put('/{id}', [HackathonController::class, 'update'])->name('update');
        Route::post('/{id}/distribute-judging', [HackathonController::class, 'distributeJudging'])->name('distribute');
        Route::post('/{id}/publish-results', [HackathonController::class, 'publishResults'])->name('publish');
    });
});
