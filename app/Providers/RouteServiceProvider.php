<?php

namespace App\Providers;

use App\Models\v1\PasswordCodeResets;
use App\Traits\Extendable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    use Extendable;

    /**
     * The path to the "home" route for your application.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/console/user';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     *
     * @return void
     */
    public function boot()
    {
        $this->configureRateLimiting();

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api/v1')
                ->group(base_path('routes/v1/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }

    /**
     * Configure the rate limiters for the application.
     *
     * @return void
     */
    protected function configureRateLimiting()
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('code-requests', function (Request $request) {
            if ($request->route()->named('verification.send')) {
                $check = $request->user();
                $datetime = $check->last_attempt ?? now()->subMinutes(config('settings.token_lifespan', 30) + 1);
                $action = 'activate your account';
            } else {
                $check = PasswordCodeResets::whereEmail($request?->email)->first();
                $datetime = $check->created_at ?? now()->subMinutes(config('settings.token_lifespan', 30) + 1);
                $action = 'reset your password';
            }

            $time_left = config('settings.token_lifespan', 30) - $datetime->diffInMinutes(now());
            $try_at = $datetime->addMinutes(config('settings.token_lifespan', 30));

            return ($time_left <= 0)
                ? Limit::none()
                : $this->buildResponse([
                    'message' => __("We already sent a message to help you {$action}, you can try again after :0 minutes.", [$time_left]),
                    'status' => 'success',
                    'time_left' => $time_left,
                    'try_at' => $try_at,
                    'status_code' => 429,
                ]);
        });
    }
}
