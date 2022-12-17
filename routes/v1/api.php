<?php

use App\Http\Controllers\Api\v1\BulletinController;
use App\Http\Controllers\Api\v1\CategoryController;
use App\Http\Controllers\Api\v1\CommunityController;
use App\Http\Controllers\Api\v1\CompanyController as V1CompanyController;
use App\Http\Controllers\Api\v1\FeedbackController;
use App\Http\Controllers\Api\v1\Provider\ServiceController;
use App\Http\Controllers\Api\v1\SearchController;
use App\Http\Controllers\Api\v1\Tools\ImageController;
use App\Http\Controllers\Api\v1\User\Company\PaymentController;
use App\Http\Controllers\Api\v1\User\UsersController;
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
        Route::get('/{company:slug}/events', 'events')->name('events');
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