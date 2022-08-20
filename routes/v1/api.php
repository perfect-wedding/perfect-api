<?php

use App\Http\Controllers\Api\v1\CategoryController;
use App\Http\Controllers\Api\v1\CompanyController as V1CompanyController;
use App\Http\Controllers\Api\v1\ServiceController;
use App\Http\Controllers\Api\v1\Tools\ImageController;
use App\Http\Controllers\Api\v1\User\Account;
use App\Http\Controllers\Api\v1\User\AlbumController;
use App\Http\Controllers\Api\v1\User\CompanyController;
use App\Http\Controllers\Api\v1\User\OffersController;
use App\Http\Controllers\Api\v1\User\ServiceController as UserServiceController;
use App\Http\Controllers\Api\v1\User\TransactionController;
use App\Http\Controllers\Api\v1\User\VisionBoardController;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;

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

Route::middleware(['auth:sanctum'])->group(function () {
    Route::name('account.')->prefix('account')->group(function () {
        Route::get('/', [Account::class, 'index'])->name('index');
        Route::get('/wallet', [Account::class, 'wallet'])->name('wallet');
        Route::put('update', [Account::class, 'update'])->name('update');
        Route::put('update-password', [Account::class, 'updatePassword'])->name('update.password');
        Route::put('update-profile-picture', [Account::class, 'updateProfilePicture'])->name('update.profile.picture');
        Route::put('default-company', [Account::class, 'updateDefaultCompany'])->name('update.default.comapny');

        Route::name('companies.')->prefix('companies')->controller(CompanyController::class)->group(function () {
            Route::put('{company}/update-profile-picture/{type}', 'changeDp')->name('changeDp');
            Route::apiResource('{company}/services', UserServiceController::class);
            Route::name('services.')->prefix('{company}/services')->controller(UserServiceController::class)->group(function () {
                Route::get('/type/{type?}', 'index')->name('type');
                Route::name('offers.')->prefix('{service}/offers')->controller(OffersController::class)->group(function () {
                    Route::get('/', 'index')->name('offers');
                    Route::get('/{offer}', 'show')->name('show');
                    Route::post('/', 'store');
                    Route::put('/{offer}', 'update');
                    Route::delete('/{offer}', 'destroy');
                });
            });
        });
        Route::apiResource('companies', CompanyController::class);

        Route::get('transactions/{status?}', [TransactionController::class, 'index'])->name('index');
        Route::get('transactions/{reference}/invoice', [TransactionController::class, 'invoice'])->name('invoice');
        Route::apiResource('transactions', TransactionController::class)->except('index');

        Route::apiResource('albums', AlbumController::class);
        Route::apiResource('boards', VisionBoardController::class);
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
    });

    Route::name('images.')->prefix('images')->controller(ImageController::class)->group(function () {
        Route::post('/storage', 'store')->name('store');
        Route::delete('/storage/{id}', 'destroy')->name('destroy');
        Route::put('/storage/{id}', 'update')->name('update');
        Route::put('/{type}/grid/{imageable_id}', 'updateGrid')->name('grid.update');
    });

    Route::name('services.')->prefix('services')->controller(ServiceController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/{service}/reviews', 'reviews')->name('service.reviews');
        Route::get('/{service:slug}', [ServiceController::class, 'show'])->name('service.show');
        Route::post('/checkout', 'checkout')->name('service.checkout');
    });

    Route::get('/playground', function () {
        return (new Shout())->viewable();
    })->name('playground');
});

require __DIR__.'/auth.php';
