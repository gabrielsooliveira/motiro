<?php

use App\Http\Controllers\Public\NewsController;
use Illuminate\Support\Facades\Route;

Route::prefix('noticias')->name('news.')->group(function () {
    Route::get('/', [NewsController::class, 'index'])->name('index');
    Route::get('/{news}', [NewsController::class, 'show'])->name('show');
});