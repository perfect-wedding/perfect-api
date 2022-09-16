<?php

use App\Http\Controllers\Api\v1\Concierge\CompanyController;
use App\Http\Controllers\Api\v1\Concierge\TasksController;
use Illuminate\Support\Facades\Route;

Route::name('concierge.')->prefix('concierge')->middleware(['auth:sanctum'])->group(function () {
    Route::name('tasks.')->prefix('tasks')->controller(TasksController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::get('/completed', 'completed')->name('completed');
        Route::get('/{task}', 'show')->name('show');
        Route::delete('/{task}', 'destroy')->name('destroy');
    });

    Route::name('companies.')->prefix('companies')->controller(CompanyController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/verify/{task}', 'verify')->name('verify');
    });
});
