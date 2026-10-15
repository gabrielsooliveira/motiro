<?php

use App\Http\Controllers\Auth\ContextController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\Participant\LoginController;
use App\Http\Controllers\Auth\Participant\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Public\SiteController;
use Illuminate\Support\Facades\Route;

Route::middleware(['guest'])->group(function () {
    // Rotas de recuperação de senha
    Route::get('/forgot-password', [ForgotPasswordController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'store'])->name('password.email');
    Route::get('/reset-password/{token}', [ResetPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [ResetPasswordController::class, 'store'])->name('password.update');

    // Rotas unificadas/legadas de autenticação
    Route::get('/login', [SiteController::class, 'selectLogin'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');

    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])->name('register.store');
});

Route::middleware(['auth'])->group(function () {
    Route::post('/logout', [LogoutController::class, 'store'])->name('logout');

    // Context resolution and switching
    Route::get('/select-context', [ContextController::class, 'select'])->name('context.select');
    Route::post('/switch-context', [ContextController::class, 'switch'])->name('context.switch');
});