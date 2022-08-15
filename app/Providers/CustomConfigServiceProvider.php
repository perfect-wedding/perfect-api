<?php

namespace App\Providers;

use App\Traits\Meta;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\ServiceProvider;
use PDO;

defined('DB_VERSION') || define('DB_VERSION', str($dbv = request()->header('db-version'))->prepend($dbv ? 'v' : null)->toString());
defined('API_VERSION') || define('API_VERSION', str(request()->path())->explode('/')->skip(1)->first());
defined('USER_MODEL') || define('USER_MODEL', 'App\Models\\'.API_VERSION.'\\User');

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
        config([
            'auth.providers.users.model' => USER_MODEL,
            'app.api' => [
                'version' => [
                    'string' => API_VERSION,
                    'code' => str(API_VERSION)->remove('v')->append('.0.0')->toString(),
                    'int' => (int) str(API_VERSION)->remove('v')->toString(),
                ],
            ],
        ]);

        $db_version = (DB_VERSION ? DB_VERSION : API_VERSION);

        if ($db_version !== 'v1' && config('app.api.version.int') > 1) {
            config([
                'database.default' => str(config('database.default'))->append('_'.$db_version)->toString(),
                'database.connections.mysql_'.$db_version => collect([
                    'driver' => env('DB_DRIVER', 'mysql'),
                    'url' => env('DATABASE_URL'),
                    'host' => env('DB_HOST', '127.0.0.1'),
                    'port' => env('DB_PORT', '3306'),
                    'database' => str(env('DB_DATABASE'))->append('_'.$db_version)->toString(),
                    'username' => env('DB_USERNAME', 'forge'),
                    'password' => env('DB_PASSWORD', ''),
                ])->merge(env('DB_DRIVER', 'mysql') === 'mysql' ? [
                    'unix_socket' => env('DB_SOCKET', ''),
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
        }

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
