<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Support\Stringable;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Str::macro('isBool', function (string $value) {
            return preg_match('/^[0-1]{1}+$|^(?:true|false|on|off)+$/', $value) || is_bool($value);
        });

        Stringable::macro('isBool', function () {
            return new Stringable(Str::isBool($this->value));
        });

        if($this->app->environment('production')) {
            \URL::forceScheme('https');
        }
    }
}