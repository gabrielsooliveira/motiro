<?php

use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\Participant\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\Participant\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use Illuminate\Support\Facades\Route;

// ============================================================
// Portal do Participante — Autenticação
// ============================================================
Route::middleware(['guest'])->prefix('participante')->name('participant.')->group(function () {
    Route::get('/entrar', [LoginController::class, 'createParticipant'])->name('login');
    Route::post('/entrar', [LoginController::class, 'storeParticipant'])->name('login.store');

    Route::get('/cadastro', [RegisterController::class, 'create'])->name('register');
    Route::post('/cadastro', [RegisterController::class, 'store'])->name('register.store');
});

// ============================================================
// Portal da Organização — Autenticação
// ============================================================
Route::middleware(['guest'])->prefix('organizacao')->name('org.')->group(function () {
    Route::get('/entrar', [LoginController::class, 'createOrganization'])->name('login');
    Route::post('/entrar', [LoginController::class, 'storeOrganization'])->name('login.store');

    Route::get('/cadastro', [RegisterController::class, 'create'])->name('register');
    Route::post('/cadastro', [RegisterController::class, 'store'])->name('register.store');
});

// ============================================================
// Rotas compartilhadas de autenticação (sem prefixo de portal)
// Mantêm compatibilidade com código existente
// ============================================================
Route::middleware(['guest'])->group(function () {
    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])->name('register.store');

    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');

    // Recuperação de senha
    Route::get('/forgot-password', [ForgotPasswordController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'store'])->name('password.email');
    Route::get('/reset-password/{token}', [ResetPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [ResetPasswordController::class, 'store'])->name('password.update');
});

// ============================================================
// Rotas autenticadas — logout e contexto
// ============================================================
Route::middleware(['auth'])->group(function () {
    Route::post('/logout', [LogoutController::class, 'store'])->name('logout');

    // Context resolution and switching
    Route::get('/select-context', [\App\Http\Controllers\Auth\ContextController::class, 'select'])->name('context.select');
    Route::post('/switch-context', [\App\Http\Controllers\Auth\ContextController::class, 'switch'])->name('context.switch');
});
