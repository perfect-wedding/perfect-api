<?php

namespace App\Traits;

use App\Models\v1\User;
use Illuminate\Database\Eloquent\Model;

trait Permissions
{
    protected $user;

    protected $allowed = [
        'admin' => [
            'admins',
            'advert.manage',
            'bulletin.manage',
            'categories',
            'company.delete',
            'company.manage',
            'company.create',
            'company.update',
            'configuration',
            'concierge.manage',
            'concierge.verify',
            'concierge.task',
            'content.create',
            'content.update',
            'content.delete',
            'dashboard',
            'feedback.manage',
            'front_content',
            'giftshop',
            'orders.list',
            'orders.order',
            'orders.update',
            'orders.delete',
            'orders.manage',
            'plan.manage',
            'subscriptions',
            'transactions',
            'users.delete',
            'users.list',
            'users.manage',
            'users.update',
            'users.user',
            'users.verify',
            'website',
        ],
        'manager' => [
            'dashboard',
            'subscriptions',
            'transactions',
            'users.user',
            'users.list',
            'feedback.manage',
            'orders.list',
            'orders.order',
            'giftshop',
        ],
        'user' => [
            //
        ],
    ];

    /**
     * Set the user
     *
     * @param  App\Models\User  $user
     * @return Permissions
     */
    public function setPermissionsUser(User $user)//: Permissions
    {
        $this->privileges = $user->privileges;

        return $this;
    }

    /**
     * Set the user
     *
     * @param  App\Models\User  $user
     * @return bool
     */
    public function isOwner(User $user, $item): bool
    {
        return ($item->user_id ?? $item->user->id ?? null) === $user->id;
    }

    /**
     * Check if the user has the requested permission
     *
     * @param  string  $permission
     * @return string|bool
     */
    public function checkPermissions(string|Model $permission): string|bool
    {
        if ($this->listPriviledges()->contains($permission)) {
            foreach (($this->privileges ?? []) as $user_permission) {
                if (isset($this->allowed[$user_permission])) {
                    if (collect($this->allowed[$user_permission])->contains($permission)// ||
                        // collect($this->allowed[$user_permission])->contains(str($permission)->explode('.')->first())
                    ) {
                        return true;
                    } elseif (in_array($permission, $this->allowed[$user_permission], true)) {
                        return true;
                    }
                } elseif (str($user_permission)->is($permission)) {
                    return true;
                }
            }
        } elseif (($permission instanceof Model &&
                  $permission->user_id && $permission->user_id === $this->auth_user->id) ||
                  (is_numeric($permission) && $permission === $this->auth_user->id)
        ) {
            return true;
        }

        return 'You do not have permission to view or perform this action.';
    }

    /**
     * Get a list of all available privileges
     *
     * @return \Illuminate\Support\Collection<TKey, TValue>
     */
    public function listPriviledges($key = null)
    {
        if ($key && collect($this->allowed)->has($key)) {
            return collect($this->allowed[$key])->flatten();
        }

        return collect($this->allowed)->flatten();
    }

    /**
     * Get a list of all available permissions
     *
     * @return \Illuminate\Support\Collection<TKey, TValue>
     */
    public function getPermissions()
    {
        $permissions = [];
        foreach (($this->privileges ?? []) as $user_permission) {
            $permissions[] = $this->allowed[$user_permission] ?? [];
        }

        return collect($permissions)->flatten()->toArray();
    }

    /**
     * Check if the user has the admin priviledge
     *
     * @return bool
     */
    public function isAdmin(): bool
    {
        foreach (($this->privileges ?? []) as $user_permission) {
            if ($user_permission === 'admin') {
                return true;
            }
        }

        return false;
    }
}
