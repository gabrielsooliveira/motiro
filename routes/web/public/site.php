<?php

use App\Http\Controllers\Public\SiteController;
use Illuminate\Support\Facades\Route;

Route::get('/', [SiteController::class, 'home'])->name('home');
Route::get('/login-area', [SiteController::class, 'selectLogin'])->name('select.login');
Route::get('/login', [SiteController::class, 'selectLogin'])->name('login');
Route::post('/logout', [\App\Http\Controllers\Auth\LogoutController::class, 'store'])->name('logout');