<?php

namespace App\Providers;

use App\Traits\Meta;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;
use PDO;

class CustomConfigServicePreProvider extends ServiceProvider
{
    use Meta;

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
    public function boot(Request $request)
    {
        config([
            'services.google' => [
                'client_secret' => env('GOOGLE_CLIENT_SECRET'),
                'client_id' => env('GOOGLE_CLIENT_ID'),
                'redirect' => config('google.redirect'),
            ],
        ]);
    }
}