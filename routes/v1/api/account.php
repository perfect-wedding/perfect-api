<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\v1\User\Account;
use App\Http\Controllers\Api\v1\User\AlbumController;
use App\Http\Controllers\Api\v1\User\Company\CompanyController;
use App\Http\Controllers\Api\v1\User\Company\EventController;
use App\Http\Controllers\Api\v1\User\Company\InventoryController;
use App\Http\Controllers\Api\v1\User\Company\OffersController;
use App\Http\Controllers\Api\v1\User\Company\OrderController as CompanyOrderController;
use App\Http\Controllers\Api\v1\User\Company\ServiceController as CompanyServiceController;
use App\Http\Controllers\Api\v1\User\FeaturedController;
use App\Http\Controllers\Api\v1\User\GenericRequestController;
use App\Http\Controllers\Api\v1\User\NotificationController;
use App\Http\Controllers\Api\v1\User\OrderController;
use App\Http\Controllers\Api\v1\User\OrderRequestController;
use App\Http\Controllers\Api\v1\User\PlanController;
use App\Http\Controllers\Api\v1\User\TransactionController;
use App\Http\Controllers\Api\v1\User\VisionBoardController;

Route::middleware(['auth:sanctum'])->group(function () {
    Route::name('account.')->prefix('account')->group(function () {
        Route::get('/', [Account::class, 'index'])->name('index');
        Route::get('/profile', [Account::class, 'profile'])->name('profile');
        Route::get('/wallet', [Account::class, 'wallet'])->name('wallet');
        Route::put('/wallet/fund/{action?}', [Account::class, 'fundWallet'])->name('fund.wallet');
        Route::post('/wallet/withdrawal', [Account::class, 'withdrawal'])->name('withdrawal');
        Route::put('update', [Account::class, 'update'])->name('update');
        Route::put('update/bank', [Account::class, 'updateBank'])->name('update.bank');
        Route::patch('update/verify', [Account::class, 'saveVerifiedData'])->name('update.verify');
        Route::put('update-password', [Account::class, 'updatePassword'])->name('update.password');
        Route::put('update-profile-picture', [Account::class, 'updateProfilePicture'])->name('update.profile.picture');
        Route::put('default-company', [Account::class, 'updateDefaultCompany'])->name('update.default.comapny');


        Route::post('verify/{type?}', [Account::class, 'identityPassVerification'])->name('verify.account');

        Route::name('companies.')->prefix('companies')->controller(CompanyController::class)->group(function () {
            Route::put('{company}/update-profile-picture/{type}', 'changeDp')->name('changeDp');
            Route::get('{company}/customers', 'customers')->name('customers');
            Route::apiResource('{company}/services', CompanyServiceController::class);
            Route::apiResource('{company}/inventories', InventoryController::class);
            Route::name('services.')->prefix('{company}/services')->controller(CompanyServiceController::class)->group(function () {
                Route::get('/type/{type?}', 'index')->name('type');
                Route::name('offers.')->prefix('{service}/offers')->controller(OffersController::class)->group(function () {
                    Route::get('/', 'index')->name('offers');
                    Route::get('/{offer}', 'show')->name('show');
                    Route::post('/', 'store');
                    Route::put('/{offer}', 'update');
                    Route::delete('/{offer}', 'destroy');
                });
            });

            Route::name('events.')->prefix('{company}')->controller(EventController::class)->group(function () {
                Route::apiResource('/events', EventController::class);
            });

            Route::name('notifications.')
                ->prefix('notifications')
                ->controller(NotificationController::class)->group(function () {
                    Route::get('/', 'company')->name('index');
                    Route::put('/mark/as/read', 'markAsRead')->name('read');
                    Route::post('/request/change/{notification}/{action}', 'change')->name('change');
                });
        });

        Route::name('notifications.')
            ->prefix('notifications')
            ->controller(NotificationController::class)->group(function () {
                Route::get('/', 'account')->name('index');
                Route::put('/mark/{id}', 'markAsRead')->name('read');
                Route::delete('/{id}', 'destroy')->name('destroy');
        });

        // Orders
        Route::name('orders.')->prefix('orders')->controller(OrderRequestController::class)->group(function () {
            Route::get('/requests/{status?}', 'index')->name('index');
            Route::get('/requests/{service}/{status?}', 'check')->name('check.request');
            Route::get('/requests/{service}/{status?}/{type?}', 'check')->name('check.request.by.type');
            Route::delete('/requests/{order_request}', 'destroy')->name('delete');
        });

        Route::get('/company/stats', [CompanyController::class, 'loadStats']);
        Route::get('/company/orders', [CompanyOrderController::class, 'index']);
        Route::delete('/companies/delete/{company}', [CompanyController::class, 'deleteCompany'])->name('deleteCompany');
        Route::apiResource('generic/requests', GenericRequestController::class);
        Route::apiResource('companies', CompanyController::class);

        Route::get('transactions/{reference}/invoice', [TransactionController::class, 'invoice'])->name('invoice');
        Route::get('transactions/{status?}', [TransactionController::class, 'index'])->name('index');
        Route::apiResource('transactions', TransactionController::class)->except('index');
        Route::apiResource('orders', OrderController::class);
        Route::put('orders/{order?}/status/request', [OrderController::class, 'updateStatusRequest'])->name('dispute');
        Route::post('orders/{order?}/dispute', [OrderController::class, 'dispute'])->name('dispute');
        Route::post('orders/{order?}/review', [OrderController::class, 'review'])->name('review');

        Route::post('albums/{album}/request-link/{action?}', [AlbumController::class, 'requestLink'])->name('request.link');
        Route::post('plans/{plan}/subscribe/{action?}', [PlanController::class, 'subscribe'])->name('subscribe');
        Route::apiResource('albums', AlbumController::class);
        Route::apiResource('boards', VisionBoardController::class);
        Route::apiResource('plans', PlanController::class)->only(['index', 'show']);
        Route::apiResource('featureds', FeaturedController::class)->only(['index', 'show']);
    });
});
