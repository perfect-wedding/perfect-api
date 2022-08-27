<?php

use Illuminate\Support\Facades\Route;
use Laravel\Socialite\Facades\Socialite;

Route::get('/auth/google/redirect', function () {
    return Socialite::driver('google')->stateless()->redirect()->getTargetUrl();
})->name('google.redirect');

Route::get('/auth/google/callback', function () {
    $user = Socialite::driver('google')->stateless()->user();

    // $user->token
})->name('google.callback');