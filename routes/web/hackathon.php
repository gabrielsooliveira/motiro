<?php

use App\Http\Controllers\Organization\HackathonController;
use Illuminate\Support\Facades\Route;

Route::prefix('hackathons')->name('hackathon.')->group(function () {
    Route::get('/', [HackathonController::class, 'index'])->name('index');
    Route::get('/{slug}', [HackathonController::class, 'show'])->name('show');
});
