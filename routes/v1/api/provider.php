<?php

use App\Http\Controllers\Api\v1\Provider\OrderController;
use Illuminate\Support\Facades\Route;

Route::name('provider.')->prefix('provider')->middleware(['auth:sanctum'])->group(function () {
    Route::name('orders.')->prefix('orders')->controller(OrderController::class)->group(function () {
        Route::get('/calendar', 'index')->name('calendar');
    });
});