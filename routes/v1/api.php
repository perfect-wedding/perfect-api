<?php

use App\Http\Controllers\Api\v1\BulletinController;
use App\Http\Controllers\Api\v1\CategoryController;
use App\Http\Controllers\Api\v1\CommunityController;
use App\Http\Controllers\Api\v1\CompanyController as V1CompanyController;
use App\Http\Controllers\Api\v1\FeedbackController;
use App\Http\Controllers\Api\v1\Provider\ServiceController;
use App\Http\Controllers\Api\v1\SearchController;
use App\Http\Controllers\Api\v1\Tools\ImageController;
use App\Http\Controllers\Api\v1\User\Account;
use App\Http\Controllers\Api\v1\User\AlbumController;
use App\Http\Controllers\Api\v1\User\Company\CompanyController;
use App\Http\Controllers\Api\v1\User\Company\EventController;
use App\Http\Controllers\Api\v1\User\Company\InventoryController;
use App\Http\Controllers\Api\v1\User\Company\OffersController;
use App\Http\Controllers\Api\v1\User\Company\OrderController as CompanyOrderController;
use App\Http\Controllers\Api\v1\User\Company\PaymentController;
use App\Http\Controllers\Api\v1\User\Company\ServiceController as CompanyServiceController;
use App\Http\Controllers\Api\v1\User\FeaturedController;
use App\Http\Controllers\Api\v1\User\NotificationController;
use App\Http\Controllers\Api\v1\User\OrderController;
use App\Http\Controllers\Api\v1\User\OrderRequestController;
use App\Http\Controllers\Api\v1\User\PlanController;
use App\Http\Controllers\Api\v1\User\TransactionController;
use App\Http\Controllers\Api\v1\User\UsersController;
use App\Http\Controllers\Api\v1\User\VisionBoardController;
use App\Http\Controllers\Api\v1\Warehouse\InventoryController as WarehouseInventoryController;
// use App\Services\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use ToneflixCode\LaravelFileable\Media;

header('SameSite:  None');

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Load Extra Routes
if (file_exists(base_path('routes/v1/api'))) {
    array_filter(File::files(base_path('routes/v1/api')), function ($file) {
        if ($file->getExtension() === 'php') {
            require_once $file->getPathName();
        }
    });
}

Broadcast::routes(['middleware' => ['auth:sanctum']]);

Route::get('secure/image/{file}', function ($file) {
    return (new Media)->privateFile($file);
})->middleware(['window_auth'])->name('secure.image');

Route::post('community/feedback', [CommunityController::class, 'feedback'])->name('community.feedback');
Route::post('community/join', [CommunityController::class, 'join'])->name('community.join');

Route::middleware(['auth:sanctum'])->group(function () {
    Route::name('account.')->prefix('account')->group(function () {
        Route::get('/', [Account::class, 'index'])->name('index');
        Route::get('/profile', [Account::class, 'profile'])->name('profile');
        Route::get('/wallet', [Account::class, 'wallet'])->name('wallet');
        Route::put('/wallet/fund/{action?}', [Account::class, 'fundWallet'])->name('fund.wallet');
        Route::post('/wallet/withdrawal', [Account::class, 'withdrawal'])->name('withdrawal');
        Route::put('update', [Account::class, 'update'])->name('update');
        Route::put('update/bank', [Account::class, 'updateBank'])->name('update.bank');
        Route::put('update-password', [Account::class, 'updatePassword'])->name('update.password');
        Route::put('update-profile-picture', [Account::class, 'updateProfilePicture'])->name('update.profile.picture');
        Route::put('default-company', [Account::class, 'updateDefaultCompany'])->name('update.default.comapny');

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
                    Route::get('/mark/as/read', 'markAsRead')->name('read');
                    Route::post('/request/change/{notification}/{action}', 'change')->name('change');
                });
        });

        // Orders
        Route::name('orders.')->prefix('orders')->controller(OrderRequestController::class)->group(function () {
            Route::get('/requests/{status?}', 'index')->name('index');
            Route::get('/requests/{service}/{status?}', 'check')->name('check.request');
            Route::get('/requests/{service}/{status?}/{type?}', 'check')->name('check.request.by.type');
            Route::delete('/requests/{order_request}', 'destroy')->name('delete');
        });

        Route::get('/notifications', [NotificationController::class, 'account'])->name('notifications');
        Route::get('/company/stats', [CompanyController::class, 'loadStats']);
        Route::get('/company/orders', [CompanyOrderController::class, 'index']);
        Route::delete('/companies/delete/{company}', [CompanyController::class, 'deleteCompany'])->name('deleteCompany');
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

    // Users
    Route::get('users/{user}', [UsersController::class, 'show'])->name('user.reviews');
    Route::get('users/{user}/reviews', [UsersController::class, 'reviews'])->name('user.reviews');

    Route::name('payment.')->prefix('payment')->controller(PaymentController::class)->group(function () {
        Route::post('/initialize', 'store')->name('initialize');
        Route::get('/paystack/verify/{type?}', 'paystackVerify')->name('payment.paystack.verify');
        Route::delete('/terminate', 'terminateTransaction')->name('terminate');
    });

    Route::apiResource('categories', CategoryController::class)->only(['index', 'show'])->scoped([
        'category' => 'slug',
    ]);

    Route::name('companies.')->prefix('companies')->controller(V1CompanyController::class)->group(function () {
        Route::get('/featured', 'featured')->name('featured');
        Route::get('/{company:slug}', 'show')->name('show');
        Route::name('services.')->prefix('{company:slug}/services')->controller(ServiceController::class)->group(function () {
            Route::get('/{type?}', 'companyIndex')->name('companyIndex');
        });
        Route::name('inventories.')->prefix('{company:slug}/inventories')
        ->controller(WarehouseInventoryController::class)->group(function () {
            Route::get('/{type?}', 'companyIndex')->name('companyIndex');
        });
    });

    Route::name('images.')->prefix('images')->controller(ImageController::class)->group(function () {
        Route::post('/storage', 'store')->name('store');
        Route::delete('/storage/{id}', 'destroy')->name('destroy');
        Route::put('/storage/{id}', 'update')->name('update');
        Route::put('/{type}/grid/{imageable_id}', 'updateGrid')->name('grid.update');
    });

    Route::name('bulletins.')->prefix('bulletins')->controller(BulletinController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/{bulletin}', 'show')->name('show');
    });

    Route::get('/search', [SearchController::class, 'index'])->name('companyIndex');

    Route::apiResource('/feedback', FeedbackController::class)->only(['store']);

    Route::post('/github/callback', function (Request $request) {
        Log::build([
            'driver' => 'single',
            'path' => storage_path('logs/custom.log'),
        ])->debug('Github Callback');

        return response()->json(['message' => 'OK'], 200);
    })->name('github.callback');

    Route::get('/playground', function () {
        return (new Shout())->viewable();
    })->name('playground');
});

require __DIR__.'/auth.php';
