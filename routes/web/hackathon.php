<?php

use Illuminate\Support\Facades\Route;

Route::prefix('hackathons')->name('hackathons.')->group(function () {
    Route::get('/', function () {
        return inertia('Site/Hackathon/Index');
    })->name('index');

    Route::get('/{slug}', function () {
        return inertia('Site/Hackathon/Show');
    })->name('show');
});
