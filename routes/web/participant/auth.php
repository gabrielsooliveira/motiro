<?php

use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\Participant\LoginController as ParticipantLoginController;
use App\Http\Controllers\Auth\Participant\RegisterController as ParticipantRegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use Illuminate\Support\Facades\Route;

Route::middleware(['guest'])->prefix('participante')->name('participant.')->group(function () {
    Route::get('/login', [ParticipantLoginController::class, 'index'])->name('login');
    Route::post('/login', [ParticipantLoginController::class, 'store'])->name('login.store');

    Route::get('/cadastro', [ParticipantRegisterController::class, 'index'])->name('register');
    Route::post('/cadastro', [ParticipantRegisterController::class, 'store'])->name('register.store');

    Route::get('/recuperar-senha', [ForgotPasswordController::class, 'create'])->name('password.request');
    Route::post('/recuperar-senha', [ForgotPasswordController::class, 'store'])->name('password.email');
    Route::get('/redefinir-senha/{token}', [ResetPasswordController::class, 'create'])->name('password.reset');
    Route::post('/redefinir-senha', [ResetPasswordController::class, 'store'])->name('password.update');
});