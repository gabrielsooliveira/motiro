<?php

use App\Http\Controllers\Organization\OrganizationController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->prefix('organization')->name('organization.')->group(function () {
    Route::post('/store', [OrganizationController::class, 'store'])->name('store');
});
