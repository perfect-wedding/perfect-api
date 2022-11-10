<?php

use App\Http\Controllers\Api\v1\Provider\OrderController;
use App\Http\Controllers\Api\v1\Provider\OrderRequestController;
use App\Http\Controllers\Api\v1\Provider\ServiceController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->group(function () {
    Route::name('provider.')->prefix('provider')->group(function () {
        Route::name('orders.')->prefix('orders')->controller(OrderController::class)->group(function () {
            Route::get('/calendar', 'calendar')->name('calendar');

            Route::controller(OrderRequestController::class)->group(function () {
                Route::get('/requests/{status?}', 'index')->name('request.index');
                Route::get('/requests/{service}/{status?}', 'check')->name('request.check.request');
                Route::put('/requests/{order}/{action}', 'update')->name('request.update');
                Route::post('/request', 'sendRequest')->name('send.request');
            });
        });
        Route::apiResource('orders', OrderController::class);
    });

    Route::name('services.')->prefix('services')->controller(ServiceController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/{service}/reviews', 'reviews')->name('service.reviews');
        Route::get('/{service:slug}', 'show')->name('service.show');
        Route::post('/checkout', 'checkout')->name('service.checkout');
    });
});