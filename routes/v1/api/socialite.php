<?php

use App\Http\Controllers\Api\v1\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Api\v1\Auth\RegisteredUserController;
use Illuminate\Support\Facades\Route;
use Laravel\Socialite\Facades\Socialite;

Route::get('/auth/google/redirect', function () {
    return Socialite::driver('google')->stateless()->redirect()->getTargetUrl();
})->name('google.redirect');

Route::post('/auth/google/register', [RegisteredUserController::class, 'socialCreateAccount'])->middleware('guest');
Route::post('/auth/google/login', [AuthenticatedSessionController::class, 'socialLogin'])->middleware('guest');

Route::get('/auth/google/callback', function () {
})->name('google.callback');
