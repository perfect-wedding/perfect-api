<?php

use App\Http\Controllers\Api\v1\Admin\AdminController;
use App\Http\Controllers\Api\v1\Admin\AdvertController;
use App\Http\Controllers\Api\v1\Admin\BulletinController;
use App\Http\Controllers\Api\v1\Admin\CategoryController;
use App\Http\Controllers\Api\v1\Admin\CompanyController as AdminCompanyController;
use App\Http\Controllers\Api\v1\Admin\Concierge\CompanyController;
use App\Http\Controllers\Api\v1\Admin\Concierge\TasksController;
use App\Http\Controllers\Api\v1\Admin\FeedbackController;
use App\Http\Controllers\Api\v1\Admin\GiftShop;
use App\Http\Controllers\Api\v1\Admin\GiftshopOrderController;
use App\Http\Controllers\Api\v1\Admin\GiftShopStore;
use App\Http\Controllers\Api\v1\Admin\Home\HomepageContentController;
use App\Http\Controllers\Api\v1\Admin\Home\HomepageController;
use App\Http\Controllers\Api\v1\Admin\Home\HomepageOfferingsController;
use App\Http\Controllers\Api\v1\Admin\Home\HomepageServicesController;
use App\Http\Controllers\Api\v1\Admin\Home\HomepageSlidesController;
use App\Http\Controllers\Api\v1\Admin\Home\HomepageTeamController;
use App\Http\Controllers\Api\v1\Admin\Home\HomepageTestimonialsController;
use App\Http\Controllers\Api\v1\Admin\NavigationController;
use App\Http\Controllers\Api\v1\Admin\OrderController;
use App\Http\Controllers\Api\v1\Admin\UsersController;
use App\Http\Controllers\Api\v1\Admin\WalletController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'admin'])->name('admin.')->prefix('admin')->group(function () {
    Route::get('stats', [AdminController::class, 'loadStats']);
    Route::prefix('website')->group(function () {
        Route::put('homepage/{homepage}/reorder', [HomepageController::class, 'reorder'])->name('reorder');
        Route::apiResource('homepage', HomepageController::class);
        Route::apiResource('{homepage}/content', HomepageContentController::class);
        Route::apiResource('{homepage}/slides', HomepageSlidesController::class);
        Route::apiResource('offerings', HomepageOfferingsController::class);
        Route::apiResource('services', HomepageServicesController::class);
        Route::apiResource('testimonials', HomepageTestimonialsController::class);
        Route::apiResource('team', HomepageTeamController::class);
    });

    Route::apiResource('navigations', NavigationController::class);
    Route::apiResource('bulletins', BulletinController::class);
    Route::apiResource('advertisements', AdvertController::class);
    Route::apiResource('companies', AdminCompanyController::class);
    Route::put('companies/{company}/update-profile-picture/{type}', [AdminCompanyController::class, 'changeDp'])->name('changeDp');

    Route::name('concierge.')->prefix('concierge')->group(function () {
        Route::name('tasks.')->prefix('tasks')->controller(TasksController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/status/{status}', 'index')->name('index.status');
            Route::post('/', 'store')->name('store');
            Route::get('/completed', 'completed')->name('completed');
            Route::get('/{task}', 'show')->name('show');
            Route::post('/{task}/approve', 'approve')->name('approve');
            Route::delete('/{task}', 'destroy')->name('destroy');
        });

        Route::name('companies.')->prefix('companies')->controller(CompanyController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::post('/verify/{task}', 'verify')->name('verify');
        });
    });

    Route::name('wallets.')->prefix('wallets')->controller(WalletController::class)->group(function () {
        Route::get('/withdrawals', 'withdrawals')->name('withdrawals');
        Route::post('/withdrawals/{wallet}/status', 'setStatus')->name('set.status');
    });

    Route::name('users.')->controller(UsersController::class)->group(function () {
        Route::apiResource('users', UsersController::class)->except(['store', 'update']);
    });

    Route::post('configuration', [AdminController::class, 'saveSettings']);

    Route::apiResource('categories', CategoryController::class);
    Route::apiResource('orders', OrderController::class);

    Route::apiResource('feedbacks', FeedbackController::class)->except(['update']);
    Route::put('feedbacks/status', [FeedbackController::class, 'status']);
    Route::post('feedbacks/github', [FeedbackController::class, 'github']);

    // Gift Shop
    Route::get('giftshops/{giftshop}/orders', [GiftshopOrderController::class, 'index']);
    Route::get('giftshops/{giftshop}/orders/{order}', [GiftshopOrderController::class, 'index']);
    Route::apiResource('giftshops/{giftshop}/orders', GiftshopOrderController::class)->only(['index', 'update']);
    Route::put('giftshops/{giftshop}/orders/{order?}/status/request', [GiftshopOrderController::class, 'updateStatusRequest'])->name('dispute');
    // Route::post('giftshops/{giftshop}/orders/{order?}/dispute', [GiftshopOrderController::class, 'dispute'])->name('dispute');
    // Route::post('giftshops/{giftshop}/orders/{order?}/review', [GiftshopOrderController::class, 'review'])->name('review');

    Route::post('giftshops/verify', [GiftShop::class, 'manualVerify']);
    Route::post('giftshops/invite', [GiftShop::class, 'sendInvitation']);
    Route::apiResource('giftshops', GiftShop::class);
    Route::apiResource('giftshops/{giftshop}/items', GiftShopStore::class);
});