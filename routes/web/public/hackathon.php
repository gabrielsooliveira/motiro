<?php

use App\Http\Controllers\Public\HackathonController;
use Illuminate\Support\Facades\Route;

Route::prefix('hackathons')->name('hackathon.')->group(function () {
    Route::get('/', [HackathonController::class, 'index'])->name('index');
    Route::get('/{hackathon}', [HackathonController::class, 'show'])->name('show');
});
