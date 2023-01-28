<?php

use App\Http\Controllers\Api\v1\Admin\AdminController;
use App\Http\Controllers\Api\v1\Admin\AdvertController;
use App\Http\Controllers\Api\v1\Admin\BulletinController;
use App\Http\Controllers\Api\v1\Admin\CategoryController;
use App\Http\Controllers\Api\v1\Admin\CompanyController as AdminCompanyController;
use App\Http\Controllers\Api\v1\Admin\Concierge\CompanyController;
use App\Http\Controllers\Api\v1\Admin\Concierge\TasksController;
use App\Http\Controllers\Api\v1\Admin\ContactFormController;
use App\Http\Controllers\Api\v1\Admin\FeaturedController;
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
use App\Http\Controllers\Api\v1\Admin\MailingListController;
use App\Http\Controllers\Api\v1\Admin\NavigationController;
use App\Http\Controllers\Api\v1\Admin\NewsletterController;
use App\Http\Controllers\Api\v1\Admin\OrderController;
use App\Http\Controllers\Api\v1\Admin\PlanController;
use App\Http\Controllers\Api\v1\Admin\SystemController;
use App\Http\Controllers\Api\v1\Admin\TransactionController;
use App\Http\Controllers\Api\v1\Admin\UsersController;
use App\Http\Controllers\Api\v1\Admin\WalletController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'admin'])->name('admin.')->prefix('admin')->group(function () {
    Route::get('stats', [SystemController::class, 'loadStats']);
    Route::get('stats/{type}', [SystemController::class, 'loadChartPlus']);
    Route::post('tests/{type}', [SystemController::class, 'testService']);

    Route::prefix('website')->group(function () {
        Route::put('homepage/{homepage}/reorder', [HomepageController::class, 'reorder'])->name('reorder');
        Route::apiResource('homepage', HomepageController::class);
        Route::apiResource('{homepage}/content', HomepageContentController::class);
        Route::apiResource('{homepage}/slides', HomepageSlidesController::class);
        Route::apiResource('offerings', HomepageOfferingsController::class);
        Route::apiResource('services', HomepageServicesController::class);
        Route::apiResource('testimonials', HomepageTestimonialsController::class);
        Route::apiResource('team', HomepageTeamController::class);
        Route::put('navigations/{navigation}/reorder', [NavigationController::class, 'reorder'])->name('reorder');
        Route::apiResource('navigations', NavigationController::class);
    });

    Route::prefix('community')->prefix('community')->group(function () {
        Route::post('feedbacks/newsletter', [ContactFormController::class, 'sendNewsletter'])->name('message');
        Route::post('members/newsletter', [MailingListController::class, 'sendNewsletter'])->name('message');
        Route::apiResource('feedbacks', ContactFormController::class);
        Route::apiResource('members', MailingListController::class);
        Route::apiResource('newsletters', NewsletterController::class);
    });

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

    Route::name('transactions.')->group(function () {
        // Route::get('/{status?}', [TransactionController::class, 'index'])->name('index');
        Route::get('/transactions/{reference}/invoice', [TransactionController::class, 'invoice'])->name('invoice');
        Route::apiResource('/transactions', TransactionController::class);
    });

    Route::name('wallets.')->prefix('wallets')->controller(WalletController::class)->group(function () {
        Route::get('/withdrawals', 'withdrawals')->name('withdrawals');
        Route::post('/withdrawals/{wallet}/status', 'setStatus')->name('set.status');
    });

    Route::name('users.')->controller(UsersController::class)->group(function () {
        Route::apiResource('users', UsersController::class)->except(['store', 'update']);
        Route::patch('users/action/{action?}', [UsersController::class, 'action'])->name('action');
    });

    Route::post('configuration', [AdminController::class, 'saveSettings']);
    Route::put('featureds/{featured}/visibility', [FeaturedController::class, 'visibility'])->name('featureds.visibility');

    Route::apiResource('categories', CategoryController::class);
    Route::apiResource('orders', OrderController::class);
    Route::apiResource('featureds', FeaturedController::class);
    Route::apiResource('plans', PlanController::class);

    Route::apiResource('feedbacks', FeedbackController::class)->except(['update']);
    Route::put('feedbacks/status', [FeedbackController::class, 'status']);
    Route::post('feedbacks/github', [FeedbackController::class, 'github']);

    // Gift Shop
    Route::get('giftshops/{giftshop}/orders', [GiftshopOrderController::class, 'index']);
    Route::get('giftshops/{giftshop}/orders/{order}', [GiftshopOrderController::class, 'index']);
    Route::apiResource('giftshops/{giftshop}/orders', GiftshopOrderController::class)->only(['index', 'update']);
    Route::put('giftshops/{giftshop}/orders/{order?}/status/request', [GiftshopOrderController::class, 'updateStatusRequest'])
        ->name('dispute');
    // Route::post('giftshops/{giftshop}/orders/{order?}/dispute', [GiftshopOrderController::class, 'dispute'])->name('dispute');
    // Route::post('giftshops/{giftshop}/orders/{order?}/review', [GiftshopOrderController::class, 'review'])->name('review');

    Route::post('giftshops/verify', [GiftShop::class, 'manualVerify']);
    Route::post('giftshops/invite', [GiftShop::class, 'sendInvitation']);
    Route::apiResource('giftshops', GiftShop::class);
    Route::apiResource('giftshops/{giftshop}/items', GiftShopStore::class);
});
