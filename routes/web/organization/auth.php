<?php

use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\Organization\LoginController;
use App\Http\Controllers\Auth\Organization\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use Illuminate\Support\Facades\Route;

// Rotas de login e recuperação de senha protegidas pelo middleware guest
Route::middleware(['guest'])->prefix('organizacao')->name('organization.')->group(function () {
    Route::get('/login', [LoginController::class, 'index'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');

    Route::get('/recuperar-senha', [ForgotPasswordController::class, 'create'])->name('password.request');
    Route::post('/recuperar-senha', [ForgotPasswordController::class, 'store'])->name('password.email');
    Route::get('/redefinir-senha/{token}', [ResetPasswordController::class, 'create'])->name('password.reset');
    Route::post('/redefinir-senha', [ResetPasswordController::class, 'store'])->name('password.update');
});

// Rotas de cadastro de organização (permite convidados e usuários já autenticados)
Route::prefix('organizacao')->name('organization.')->group(function () {
    Route::get('/cadastro', [RegisterController::class, 'index'])->name('register');
    Route::post('/cadastro', [RegisterController::class, 'store'])->name('register.store');
    Route::post('/cadastro/login', [RegisterController::class, 'loginPersonalAccount'])->name('register.login');
    Route::post('/cadastro/conta', [RegisterController::class, 'registerPersonalAccount'])->name('register.account');
    Route::get('/cadastro/sucesso/{organization}', [RegisterController::class, 'success'])->name('register.success');
});
