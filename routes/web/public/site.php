<?php

use App\Http\Controllers\Public\SiteController;
use Illuminate\Support\Facades\Route;

Route::get('/', [SiteController::class, 'home'])->name('home');
Route::get('/login-area', [SiteController::class, 'selectLogin'])->name('select.login');