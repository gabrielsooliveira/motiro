<?php

use App\Http\Controllers\Organization\HackathonChallengeController;
use App\Http\Controllers\Organization\HackathonController;
use App\Http\Controllers\Organization\HackathonMemberController;
use App\Http\Controllers\Organization\HackathonParticipantController;
use App\Http\Controllers\Organization\JudgeController;
use App\Http\Controllers\Organization\OrganizationController;
use App\Http\Controllers\Organization\OrganizationDashboardController;
use App\Http\Controllers\Organization\OrganizationUserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    // Rotas de Organização sob prefixo /organizacao e /organization
    foreach (['organizacao', 'organization'] as $prefix) {
        Route::prefix($prefix)->group(function () use ($prefix) {
            // Judging (avaliadores vinculados à organização)
            Route::prefix('judging')->name('judge.')->group(function () {
                Route::get('/', [JudgeController::class, 'index'])->name('index');
                Route::get('/project/{id}', [JudgeController::class, 'evaluate'])->name('evaluate');
                Route::post('/project/{id}/evaluate', [JudgeController::class, 'storeEvaluation'])->name('evaluate.store');
                Route::post('/conflict', [JudgeController::class, 'declareConflict'])->name('conflict.declare');
            });

            // Gestão Administrativa de Hackathons
            Route::prefix('hackathons')->name('hackathon.manage.')->group(function () {
                Route::get('/create', [HackathonController::class, 'create'])->name('create');
                Route::get('/{id}/manage', [HackathonController::class, 'manage'])->name('index');
                Route::post('/', [HackathonController::class, 'store'])->name('store');
                Route::put('/{id}', [HackathonController::class, 'update'])->name('update');
                Route::post('/{id}/distribute-judging', [HackathonController::class, 'distributeJudging'])->name('distribute');
                Route::post('/{id}/publish-results', [HackathonController::class, 'publishResults'])->name('publish');

                // Gestão de Etapas do Hackathon
                Route::post('/{id}/stages', [HackathonController::class, 'storeStage'])->name('stage.store');
                Route::put('/{id}/stages/{stageId}', [HackathonController::class, 'updateStage'])->name('stage.update');
                Route::delete('/{id}/stages/{stageId}', [HackathonController::class, 'destroyStage'])->name('stage.destroy');

                // Gestão de Critérios e Pesos de Entrega
                Route::post('/{id}/criteria', [HackathonController::class, 'storeCriterion'])->name('criterion.store');
                Route::put('/{id}/criteria/{criterionId}', [HackathonController::class, 'updateCriterion'])->name('criterion.update');
                Route::delete('/{id}/criteria/{criterionId}', [HackathonController::class, 'destroyCriterion'])->name('criterion.destroy');
            });

            // Rotas institucionais
            Route::name('organization.')->group(function () use ($prefix) {
                if ($prefix === 'organization') {
                    Route::get('/', [OrganizationController::class, 'index'])->name('index');
                    Route::post('/store', [OrganizationController::class, 'store'])->name('store');
                }

                // Rotas de escopo restrito à organização ativa
                Route::middleware(['org.context'])->group(function () use ($prefix) {
                    if ($prefix === 'organizacao') {
                        Route::get('/dashboard', [OrganizationDashboardController::class, 'index'])->name('dashboard');
                    } else {
                        Route::get('/dashboard', [OrganizationDashboardController::class, 'index']);
                    }

                    // Usuários & Convites da Instituição
                    Route::prefix('users')->name('users.')->group(function () {
                        Route::get('/', [OrganizationUserController::class, 'index'])->name('index');
                        Route::post('/invite', [OrganizationUserController::class, 'invite'])->name('invite');
                        Route::put('/{id}/role', [OrganizationUserController::class, 'updateRole'])->name('update_role');
                        Route::put('/{id}/toggle-status', [OrganizationUserController::class, 'toggleStatus'])->name('toggle_status');
                        Route::delete('/{id}', [OrganizationUserController::class, 'removeMember'])->name('remove');
                    });

                    Route::delete('/invitations/{id}', [OrganizationUserController::class, 'cancelInvitation'])->name('invitations.cancel');

                    // Equipe Administrativa do Hackathon (Membros)
                    Route::prefix('hackathons/{id}/members')->name('hackathon.members.')->group(function () {
                        Route::post('/', [HackathonMemberController::class, 'store'])->name('store');
                        Route::put('/{memberId}', [HackathonMemberController::class, 'update'])->name('update');
                        Route::delete('/{memberId}', [HackathonMemberController::class, 'destroy'])->name('destroy');
                    });

                    // Desafios do Hackathon
                    Route::prefix('hackathons/{id}/challenges')->name('hackathon.challenges.')->group(function () {
                        Route::post('/', [HackathonChallengeController::class, 'store'])->name('store');
                        Route::put('/{challengeId}', [HackathonChallengeController::class, 'update'])->name('update');
                        Route::delete('/{challengeId}', [HackathonChallengeController::class, 'destroy'])->name('destroy');
                    });

                    // Gestão de Participantes e Equipes do Hackathon (Prompt Master)
                    Route::prefix('hackathons/{id}/participants')->name('hackathon.participants.')->group(function () {
                        Route::post('/assign-team', [HackathonParticipantController::class, 'assignToTeam'])->name('assign');
                        Route::post('/transfer-team', [HackathonParticipantController::class, 'transferTeam'])->name('transfer');
                        Route::delete('/{registrationId}/remove-team', [HackathonParticipantController::class, 'removeFromTeam'])->name('remove_team');
                        Route::post('/create-team', [HackathonParticipantController::class, 'createTeam'])->name('create_team');
                    });
                });
            });
        });
    }
});
