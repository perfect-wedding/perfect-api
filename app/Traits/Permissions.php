<?php

namespace App\Traits;

use App\Models\v1\User;
use Illuminate\Database\Eloquent\Model;

trait Permissions
{
    protected $user;

    protected $allowed = [
        "super-admin" => [
            "viewHorizon",
            "super",
        ],
        "admin" => [
            "admins",
            "super",
            "support",
            "website",
            "anything",
            "giftshop",
            "dashboard",
            "categories",
            "transactions",
            "front_content",
            "subscriptions",
            "configuration",
            "plan.manage",
            "advert.manage",
            "feedback.manage",
            "bulletin.manage",
            "company.delete",
            "company.manage",
            "company.create",
            "company.update",
            "concierge.task",
            "concierge.manage",
            "concierge.verify",
            "content.create",
            "content.update",
            "content.delete",
            "orders.list",
            "orders.order",
            "orders.update",
            "orders.delete",
            "orders.manage",
            "users.list",
            "users.user",
            "users.delete",
            "users.manage",
            "users.update",
            "users.verify",
        ],
        "manager" => [
            "giftshop",
            "dashboard",
            "transactions",
            "subscriptions",
            "feedback.manage",
            "users.user",
            "users.list",
            "orders.list",
            "orders.order",
        ],
        "support" => [
            "support"
        ],
        "user" => [
            "users.list",
            "users.user",
            "users.delete",
            "users.manage",
            "users.update",
            "users.verify",
        ],
    ];

    /**
     * Set the user
     *
     * @param  App\Models\User  $user
     * @return Permissions
     */
    public function setPermissionsUser(User | \App\Models\User $user)//: Permissions
    {
        // Merge the allowed permissions for super-admin and admin
        $this->allowed['super-admin'] = array_merge($this->allowed['super-admin'], $this->allowed['admin']);

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
