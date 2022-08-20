<?php

use App\Services\AppInfo;
use App\Services\Media;
use App\Http\Controllers\WebUser;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('login', ['api_welcome' => [
        'Welcome to Perfect Wedding v1' => AppInfo::basic(),
    ],
    ]);
});

Route::prefix('console')->name('console.')->group(function () {
    Route::get('/login', [WebUser::class, 'login'])
        ->middleware('guest')
        ->name('login');

    Route::post('/login', [WebUser::class, 'store'])
    ->middleware('guest');

    Route::post('/logout', [WebUser::class, 'destroy'])
    ->middleware(['web', 'auth'])
    ->name('logout');

    Route::get('/user', [WebUser::class, 'index'])
    ->middleware(['web', 'auth', 'admin'])
    ->name('user');
});

Route::get('/artisan/backup/action/{action?}', [WebUser::class, 'backup'])->middleware(['web', 'auth', 'admin']);
Route::get('/artisan/{command}/{params?}', [WebUser::class, 'artisan'])->middleware(['web', 'auth', 'admin']);


Route::get('get/images/{file}', function ($file) {
    return (new Media)->privateFile($file);
})->middleware(['window_auth'])->name('get.image');

Route::middleware(['single_auth'])->group(function () {
    Route::name('vcards.')->prefix('vcards')->group(function () {
        // Route::get('/{vcard:slug}/download', [VcardController::class, 'downloadNow'])->name('download.now');
    });
});

Route::get('downloads/secure/{filename?}', function ($filename = '') {
    if (Storage::disk('protected')->exists('backup/'.$filename)) {
        return Storage::disk('protected')->download('backup/'.$filename);
    }

    return abort(404, 'File not found');
})
->middleware(['web', 'auth', 'admin'])
->name('secure.download');