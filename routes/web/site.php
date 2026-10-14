<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Public\SiteController;

Route::get('/', [SiteController::class, 'home'])->name('home');
