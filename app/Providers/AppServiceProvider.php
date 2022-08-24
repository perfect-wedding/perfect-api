<?php

namespace App\Providers;

use App\Traits\Extendable;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Support\Stringable;
use Illuminate\Routing\UrlGenerator;

class AppServiceProvider extends ServiceProvider
{
    use Extendable;

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
    public function boot(UrlGenerator $url)
    {
        Str::macro('isBool', function (string $value) {
            return preg_match('/^[0-1]{1}+$|^(?:true|false|on|off)+$/', $value) || is_bool($value);
        });

        Stringable::macro('isBool', function () {
            return new Stringable(Str::isBool($this->value));
        });

        if(!$this->isLocalHosted() && config('settings.force_https')) {
            $url->forceScheme('https');
        }
    }
}