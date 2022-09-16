<?php

namespace App\Traits;

use Illuminate\Notifications\Notifiable as BaseNotifiable;

trait Notifiable
{
    use BaseNotifiable;

    /**
    * Get the entity's notifications.
    */
    public function scopeAccepted($query)
    {
        $query->where('data->service_order->accepted', true);
    }

    /**
    * Get the entity's notifications.
    */
    public function scopeRejected($query)
    {
        $query->where('data->service_order->accepted', '!=', true);
    }
}