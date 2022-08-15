<?php

use App\Services\AppInfo;
use App\Services\Media;
use Illuminate\Support\Facades\Route;

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
    return [
        'Welcome to Wiewsv v1' => AppInfo::basic(),
    ];
});

Route::get('get/images/{file}', function ($file) {
    return (new Media)->privateFile($file);
})->middleware(['window_auth'])->name('get.image');

Route::middleware(['single_auth'])->group(function () {
    Route::name('vcards.')->prefix('vcards')->group(function () {
        // Route::get('/{vcard:slug}/download', [VcardController::class, 'downloadNow'])->name('download.now');
    });
});
