<?php

use App\Http\Controllers\Api\v1\Provider\OrderController;
use App\Http\Controllers\Api\v1\Provider\OrderRequestController;
use Illuminate\Support\Facades\Route;

Route::name('provider.')->prefix('provider')->middleware(['auth:sanctum'])->group(function () {
    Route::name('orders.')->prefix('orders')->controller(OrderController::class)->group(function () {
        Route::get('/calendar', 'index')->name('calendar');

        Route::controller(OrderRequestController::class)->group(function () {
            Route::get('/requests/{status?}', 'index')->name('request.index');
            Route::get('/requests/{service}/{status?}', 'check')->name('request.check.request');
            Route::put('/requests/{order}/{action}', 'update')->name('request.update');
            Route::post('/request', 'sendRequest')->name('send.request');
        });
    });
});
