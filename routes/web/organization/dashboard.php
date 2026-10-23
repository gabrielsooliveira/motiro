<?php

use App\Http\Controllers\Auth\ContextController;
use App\Http\Controllers\Organization\DashboardController;
use App\Http\Controllers\Organization\HackathonController;
use App\Http\Controllers\Organization\JudgeController;
use App\Http\Controllers\Organization\OrganizationController;
use App\Http\Controllers\Organization\OrganizationUserController;
use Illuminate\Support\Facades\Route;

/**
 * Rotas de Seleção e Alternância de Contexto (Qualquer usuário autenticado)
 */
Route::middleware(['auth'])->group(function () {
    Route::get('/selecionar-contexto', [ContextController::class, 'select'])->name('context.select');
    Route::post('/selecionar-contexto', [ContextController::class, 'switch'])->name('context.switch');

    Route::prefix('organization')->name('organization.')->group(function () {
        Route::get('/select', [ContextController::class, 'select'])->name('select');
        Route::post('/select', [ContextController::class, 'switch'])->name('select.store');
        Route::post('/store', [OrganizationController::class, 'store'])->name('store');
    });

    Route::prefix('organizacao')->group(function () {
        Route::get('/selecionar', [ContextController::class, 'select']);
        Route::post('/selecionar', [ContextController::class, 'switch']);
    });
});

/**
 * Rotas do Painel Administrativo da Organização (Protegidas por contexto ativo)
 */
Route::middleware(['auth', 'org.context'])->prefix('organization')->group(function () {
    // Dashboard principal da Organização
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('organization.dashboard');

    // Gestão de Usuários & Convites da Instituição
    Route::prefix('users')->name('organization.users.')->group(function () {
        Route::get('/', [OrganizationUserController::class, 'index'])->name('index');
        Route::post('/invite', [OrganizationUserController::class, 'invite'])->name('invite');
        Route::put('/{id}/role', [OrganizationUserController::class, 'updateRole'])->name('update_role');
        Route::put('/{id}/toggle-status', [OrganizationUserController::class, 'toggleStatus'])->name('toggle_status');
        Route::delete('/{id}', [OrganizationUserController::class, 'removeMember'])->name('remove');
    });

    Route::delete('/invitations/{id}', [OrganizationUserController::class, 'cancelInvitation'])->name('organization.invitations.cancel');

    // Gestão de Hackathons
    Route::prefix('hackathons')->name('hackathon.manage.')->group(function () {
        Route::get('/create', [HackathonController::class, 'create'])->name('create');
        Route::get('/{id}/manage', [HackathonController::class, 'manage'])->name('index');
        Route::post('/', [HackathonController::class, 'store'])->name('store');
        Route::put('/{id}', [HackathonController::class, 'update'])->name('update');
        Route::patch('/{id}/status', [HackathonController::class, 'updateStatus'])->name('status');
        Route::post('/{id}/distribute-judging', [HackathonController::class, 'distributeJudging'])->name('distribute');
        Route::post('/{id}/publish-results', [HackathonController::class, 'publishResults'])->name('publish');

        Route::post('/{id}/stages', [HackathonController::class, 'storeStage'])->name('stage.store');
        Route::put('/{id}/stages/{stageId}', [HackathonController::class, 'updateStage'])->name('stage.update');
        Route::delete('/{id}/stages/{stageId}', [HackathonController::class, 'destroyStage'])->name('stage.destroy');

        Route::post('/{id}/criteria', [HackathonController::class, 'storeCriterion'])->name('criterion.store');
        Route::put('/{id}/criteria/{criterionId}', [HackathonController::class, 'updateCriterion'])->name('criterion.update');
        Route::delete('/{id}/criteria/{criterionId}', [HackathonController::class, 'destroyCriterion'])->name('criterion.destroy');
    });

    // Painel do Avaliador / Juiz
    Route::prefix('judge')->name('judge.')->group(function () {
        Route::get('/', [JudgeController::class, 'index'])->name('index');
        Route::get('/{projectId}/evaluate', [JudgeController::class, 'evaluate'])->name('evaluate');
        Route::post('/{projectId}/evaluate', [JudgeController::class, 'storeEvaluation'])->name('evaluate.store');
        Route::post('/conflict', [JudgeController::class, 'declareConflict'])->name('conflict.declare');
    });
});

// Aliases em português para URLs de organização
Route::middleware(['auth', 'org.context'])->prefix('organizacao')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/', [DashboardController::class, 'index']);
    Route::get('/users', [OrganizationUserController::class, 'index']);
    Route::get('/hackathons/create', [HackathonController::class, 'create']);
    Route::get('/hackathons/{id}/manage', [HackathonController::class, 'manage']);
});
