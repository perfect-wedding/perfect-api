<?php

use App\Http\Controllers\Api\v1\HomeController;
use App\Http\Controllers\Api\v1\Tools\ColorExtractor;
use Illuminate\Support\Facades\Route;

Route::name('home.')->controller(HomeController::class)->group(function () {
    Route::get('/get/settings', 'settings')->name('settings');
    Route::get('/get/verification/{action}/{task?}', 'verificationData')->name('verification.data');
    Route::post('/get/verification/{action}', 'verificationData')->middleware(['auth:sanctum']);

    Route::prefix('home')->group(function () {
        Route::get('/', 'index')->name('list');
        Route::get('index', 'page')->name('index');
        Route::get('/{id}', 'page')->name('page');
    });

    Route::post('/get/color-palette', [ColorExtractor::class, 'index'])->name('color.palette');
});