<?php

use App\Http\Controllers\Public\GuildsController;
use Illuminate\Support\Facades\Route;

Route::prefix('guilds')->name('guild.')->group(function () {
    Route::get('/', [GuildsController::class, 'index'])->name('index');
    Route::get('/{guild}', [GuildsController::class, 'show'])->name('show');
});

