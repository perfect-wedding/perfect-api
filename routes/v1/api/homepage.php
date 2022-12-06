<?php

use App\Http\Controllers\Api\v1\Advert;
use App\Http\Controllers\Api\v1\HomeController;
use App\Http\Controllers\Api\v1\Tools\ColorExtractor;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\v1\User\VisionBoardController;
use App\Http\Controllers\Api\v1\User\AlbumController;

Route::name('home.')->controller(HomeController::class)->group(function () {
    Route::get('/get/settings', 'settings')->name('settings');
    Route::get('/get/navigations', 'navigations')->name('navigations');
    Route::get('/get/verification/{action}/{task?}', 'verificationData')->name('verification.data');
    Route::post('/get/verification/{action}', 'verificationData')->middleware(['auth:sanctum']);

    Route::prefix('home')->group(function () {
        Route::get('/', 'index')->name('list');
        Route::get('index', 'page')->name('index');
        Route::get('/{id}', 'page')->name('page');
    });

    Route::post('/get/color-palette', [ColorExtractor::class, 'index'])->name('color.palette');

    Route::name('shared.')->prefix('shared')->group(function () {
        Route::get('vision/boards/{board}', [VisionBoardController::class, 'showShared'])->name('vision.boards.show');
        Route::get('albums/{token}', 'loadAlbum')->name('albums.album');
    });

    Route::get('/content/placement', [Advert::class, 'index'])->middleware(['auth:sanctum'])->name('ad.placement');
    Route::get('/content/placement/guest', [Advert::class, 'index'])->name('ad.placement');
});
