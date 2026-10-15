<?php

use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\Organization\LoginController;
use App\Http\Controllers\Auth\Organization\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use Illuminate\Support\Facades\Route;

// Rotas de autenticação estrita para visitantes (guest)
Route::middleware(['guest'])->group(function () {
    Route::prefix('organizacao')->name('organization.')->group(function () {
        Route::get('/login', [LoginController::class, 'index'])->name('login');
        Route::get('/entrar', [LoginController::class, 'index']);
        Route::post('/login', [LoginController::class, 'store'])->name('login.store');
        Route::post('/entrar', [LoginController::class, 'store']);

        Route::get('/recuperar-senha', [ForgotPasswordController::class, 'create'])->name('password.request');
        Route::post('/recuperar-senha', [ForgotPasswordController::class, 'store'])->name('password.email');
        Route::get('/redefinir-senha/{token}', [ResetPasswordController::class, 'create'])->name('password.reset');
        Route::post('/redefinir-senha', [ResetPasswordController::class, 'store'])->name('password.update');
    });
});

// Fluxo de Cadastro de Organização em Etapas (acessível por visitantes e usuários já autenticados)
Route::prefix('organizacao')->name('organization.')->group(function () {
    Route::get('/cadastro', [RegisterController::class, 'create'])->name('register');
    Route::post('/cadastro', [RegisterController::class, 'store'])->name('register.store');
    Route::post('/cadastro/entrar', [RegisterController::class, 'loginPersonalAccount'])->name('register.login');
    Route::post('/cadastro/conta', [RegisterController::class, 'registerPersonalAccount'])->name('register.account');
    Route::get('/cadastro/sucesso/{organization}', [RegisterController::class, 'success'])->name('register.success');
});

Route::prefix('organization')->group(function () {
    Route::get('/register', [RegisterController::class, 'create']);
    Route::post('/register', [RegisterController::class, 'store']);
});