<?php

namespace App\Events;

use App\Models\User;
use App\Models\v1\User as v1User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PhoneVerified
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The verified user.
     *
     * @var App\Models\User
     */
    public $user;

    /**
     * Create a new event instance.
     *
     * @param  App\Models\User|App\Models\v1\User  $user
     * @return void
     */
    public function __construct(v1User|User $user)
    {
        $this->user = $user;
    }
}