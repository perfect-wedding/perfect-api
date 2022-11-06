<?php

namespace App\Providers;

use App\EnumsAndConsts\HttpStatus;
use App\Models\v1\User;
use App\Traits\Extendable;
use App\Traits\Permissions;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    use Permissions, Extendable;

    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        Gate::define('can-do', function (User $user, $permission, $item = null) {
            return ($check = $this->setPermissionsUser($user)->checkPermissions($permission)) === true
                ? Response::allow()
                : Response::deny($check, HttpStatus::FORBIDDEN);
        });

        Gate::define('be-owner', function ($user, $item = null) {
            return $this->isOwner($user, $item) === true
            ? Response::allow()
            : Response::deny('You do not have permission to view or perform this action.', HttpStatus::FORBIDDEN);
        });
    }
}