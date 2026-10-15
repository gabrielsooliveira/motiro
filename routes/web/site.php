<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Public\SiteController;
use App\Http\Controllers\Public\HackathonController;

Route::get('/', [SiteController::class, 'home'])->name('home');
Route::get('/login-area', [SiteController::class,'selectLogin'])->name('select.login');

Route::prefix('hackathons')->name('hackathon.')->group(function () {
    Route::get('/', [HackathonController::class, 'list'])->name('list');
    Route::get('/{slug}', [HackathonController::class, 'show'])->name('show');
});