<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return inertia('Site/Index');
})->name('index');
