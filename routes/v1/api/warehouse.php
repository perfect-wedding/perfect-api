<?php

use App\Http\Controllers\Api\v1\Warehouse\InventoryController;
use App\Http\Controllers\Api\v1\Warehouse\OrderController;
use App\Http\Controllers\Api\v1\Warehouse\OrderRequestController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->group(function () {
    Route::name('warehouse.')->prefix('warehouse')->group(function () {
        Route::name('orders.')->prefix('orders')->controller(OrderController::class)->group(function () {
            Route::get('/calendar', 'index')->name('calendar');

            Route::controller(OrderRequestController::class)->group(function () {
                Route::get('/requests/{status?}', 'index')->name('request.index');
                Route::get('/requests/{service}/{status?}', 'check')->name('request.check.request');
                Route::put('/requests/{order}/{action}', 'update')->name('request.update');
                Route::post('/request', 'sendRequest')->name('send.request');
            });
        });
        Route::apiResource('orders', OrderController::class);
    });

    Route::name('inventories.')->prefix('inventories')->controller(InventoryController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/category/{category}', 'category')->name('category');
        Route::get('/{inventory}/reviews', 'reviews')->name('inventory.reviews');
        Route::get('/{inventory:slug}', 'show')->name('inventory.show');
        Route::post('/checkout', 'checkout')->name('inventory.checkout');
    });
});