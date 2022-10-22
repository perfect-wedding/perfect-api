<?php

use App\Http\Controllers\Api\v1\GiftShopController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->group(function () {
    Route::name('giftshops.')->prefix('giftshops')->controller(GiftShopController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/{giftshop}', 'show')->name('show');
        Route::get('/{giftshop}/{item}', 'showItem')->name('show.item');
        Route::post('/', 'create')->name('create');
        Route::put('{giftshop}', 'create')->name('update');
        Route::delete('{giftshop}', 'create')->name('delete');
        Route::get('/category/{category}', 'category')->name('category');
        Route::get('/{giftshop}/reviews', 'reviews')->name('reviews');
        Route::post('/checkout', 'checkout')->name('checkout');
    });
});

Route::get('giftshops/invited/{token}', [GiftShopController::class, 'invited'])->name('giftshops.invited');