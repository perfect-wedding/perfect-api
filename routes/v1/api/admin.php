<?php

use App\Http\Controllers\Api\v1\Admin\AdminController;
use App\Http\Controllers\Api\v1\Admin\Home\HomepageContentController;
use App\Http\Controllers\Api\v1\Admin\Home\HomepageController;
use App\Http\Controllers\Api\v1\Admin\Home\HomepageOfferingsController;
use App\Http\Controllers\Api\v1\Admin\Home\HomepageServicesController;
use App\Http\Controllers\Api\v1\Admin\Home\HomepageSlidesController;
use App\Http\Controllers\Api\v1\Admin\Home\HomepageTeamController;
use App\Http\Controllers\Api\v1\Admin\Home\HomepageTestimonialsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'admin'])->name('admin.')->prefix('admin')->group(function () {
    Route::prefix('website')->group(function () {
        Route::apiResource('homepage', HomepageController::class);
        Route::apiResource('{homepage}/content', HomepageContentController::class);
        Route::apiResource('{homepage}/slides', HomepageSlidesController::class);
        Route::apiResource('offerings', HomepageOfferingsController::class);
        Route::apiResource('services', HomepageServicesController::class);
        Route::apiResource('testimonials', HomepageTestimonialsController::class);
        Route::apiResource('team', HomepageTeamController::class);
    });
    Route::post('configuration', [AdminController::class, 'saveSettings']);
});
