<?php

use App\Http\Controllers\Organization\HackathonChallengeController;
use App\Http\Controllers\Organization\HackathonMemberController;
use App\Http\Controllers\Organization\OrganizationController;
use App\Http\Controllers\Organization\OrganizationDashboardController;
use App\Http\Controllers\Organization\OrganizationUserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->prefix('organization')->name('organization.')->group(function () {
    Route::get('/', [OrganizationController::class, 'index'])->name('index');
    Route::post('/store', [OrganizationController::class, 'store'])->name('store');

    // Context-scoped institutional routes
    Route::middleware(['org.context'])->group(function () {
        // Institutional Dashboard (Section 5)
        Route::get('/dashboard', [OrganizationDashboardController::class, 'index'])->name('dashboard');

        // Institution Users & Invitations Management (Sections 6, 7, 8)
        Route::prefix('users')->name('users.')->group(function () {
            Route::get('/', [OrganizationUserController::class, 'index'])->name('index');
            Route::post('/invite', [OrganizationUserController::class, 'invite'])->name('invite');
            Route::put('/{id}/role', [OrganizationUserController::class, 'updateRole'])->name('update_role');
            Route::put('/{id}/toggle-status', [OrganizationUserController::class, 'toggleStatus'])->name('toggle_status');
            Route::delete('/{id}', [OrganizationUserController::class, 'removeMember'])->name('remove');
        });

        Route::delete('/invitations/{id}', [OrganizationUserController::class, 'cancelInvitation'])->name('invitations.cancel');

        // Hackathon Staff Team Management (Sections 9 & 10)
        Route::prefix('hackathons/{id}/members')->name('hackathon.members.')->group(function () {
            Route::post('/', [HackathonMemberController::class, 'store'])->name('store');
            Route::put('/{memberId}', [HackathonMemberController::class, 'update'])->name('update');
            Route::delete('/{memberId}', [HackathonMemberController::class, 'destroy'])->name('destroy');
        });

        // Hackathon Challenges Management
        Route::prefix('hackathons/{id}/challenges')->name('hackathon.challenges.')->group(function () {
            Route::post('/', [HackathonChallengeController::class, 'store'])->name('store');
            Route::put('/{challengeId}', [HackathonChallengeController::class, 'update'])->name('update');
            Route::delete('/{challengeId}', [HackathonChallengeController::class, 'destroy'])->name('destroy');
        });
    });
});
