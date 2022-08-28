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

$isApi = request()->isXmlHttpRequest() && str(request()->path())->explode('/')->first() === 'api';
$getVersion = $isApi ? str(request()->path())->explode('/')->skip(1)->first() : '1';
defined('DB_VERSION') || define('DB_VERSION', str($dbv = request()->header('db-version'))->prepend($dbv ? 'v' : null)->toString());
defined('API_VERSION') || define('API_VERSION', $getVersion);
defined('USER_MODEL') || define('USER_MODEL', ! $isApi ? \App\Models\User::class : 'App\Models\\'.API_VERSION.'\\User');

class CustomConfigServiceProvider extends ServiceProvider
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
        // Load Custom Helpers
        if (file_exists(app_path('Helpers'))) {
            array_filter(File::files(app_path('Helpers')), function (\Symfony\Component\Finder\SplFileInfo $file) {
                if ($file->getExtension() === 'php' && stripos($file->getFileName(), 'helper') !== false) {
                    require_once $file->getPathName();
                }
            });
        }

        config([
            'auth.providers.users.model' => USER_MODEL,
            'markable.user_model' => USER_MODEL,
            'app.api' => [
                'version' => [
                    'string' => API_VERSION,
                    'code' => str(API_VERSION)->remove('v')->append('.0.0')->toString(),
                    'int' => (int) str(API_VERSION)->remove('v')->toString(),
                ],
            ],
        ]);

        $db_version = (DB_VERSION ? DB_VERSION : API_VERSION);
        $db_persist = Arr::get(db_persist(), 'connections.mysql');

        if ($db_version !== 'v1' && config('app.api.version.int') > 1) {
            config([
                'database.default' => str(config('database.default'))->remove('_'.$db_version)->append('_'.$db_version)->toString(),
                "database.connections.mysql_$db_version" => collect([
                    'driver' => env('DB_DRIVER', 'mysql'),
                    'url' => env('DATABASE_URL', $db_persist['url'] ?? null),
                    'host' => env('DB_HOST', '127.0.0.1'),
                    'port' => env('DB_PORT', '3306'),
                    'database' => str(env('DB_DATABASE', $db_persist['database'] ?? null))->remove('_'.$db_version)->contains($db_version)
                        ? env('DB_DATABASE', $db_persist['database'] ?? null)
                        : str(env('DB_DATABASE', $db_persist['database'] ?? null))->remove('_'.$db_version)->append('_'.$db_version)->toString(),
                    'username' => env('DB_USERNAME', $db_persist['username'] ?? 'forge'),
                    'password' => env('DB_PASSWORD', $db_persist['password'] ?? ''),
                ])->merge(env('DB_DRIVER', 'mysql') === 'mysql' ? [
                    'unix_socket' => env('DB_SOCKET', $db_persist['unix_socket'] ?? ''),
                    'charset' => 'utf8mb4',
                    'collation' => 'utf8mb4_unicode_ci',
                    'prefix' => '',
                    'prefix_indexes' => true,
                    'strict' => true,
                    'engine' => null,
                    'options' => extension_loaded('pdo_mysql') ? array_filter([
                        PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
                    ]) : [],
                ] : (env('DB_DRIVER', 'mysql') === 'pgsql' ? [
                    'charset' => 'utf8',
                    'prefix' => '',
                    'prefix_indexes' => true,
                    'search_path' => 'public',
                    'sslmode' => 'prefer',
                ] : []))->toArray(),
            ]);
            db_persist(true);
        }

        config([
            'services.google' => [
                'client_secret' => env('GOOGLE_CLIENT_SECRET'),
                'client_id' => env('GOOGLE_CLIENT_ID'),
                'redirect' => url('api/v1/auth/google/callback'),
            ],
            'services.facebook' => [
                'client_secret' => env('FACEBOOK_CLIENT_SECRET'),
                'client_id' => env('FACEBOOK_CLIENT_ID'),
                'redirect' => url('api/v1/auth/facebook/callback'),
            ],
        ]);

        Collection::macro('paginate', function ($perPage = 15, $currentPage = null, $options = []) {
            $currentPage = $currentPage ?: (Paginator::resolveCurrentPage() ?: 1);

            return new LengthAwarePaginator(
                $this->forPage($currentPage, $perPage),
                $this->count(),
                $perPage,
                $currentPage,
                array_merge(['path' => request()->fullUrlWithoutQuery('page'), $options])
            );
        });
    }
}