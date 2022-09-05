<?php

namespace App\Traits;

use App\Services\Countdown;
use Carbon\Carbon;

trait CalculateTimeDiff
{
    /**
     * Return elapsed time based in model attribite
     *
     * @param  string $attribute
     * @return App\Services\Countdown $countdown
     */
    public function elapsed($attribute)
    {
        $carbon = new Carbon;
        $timezone = config('app.timezone');
        $countdown = new Countdown($timezone, $carbon);

        $attribute = $this->{$attribute};
        $now = Carbon::now();

        return $countdown->from($attribute)
            ->to($now)->get();
    }

    /**
     * Return until time based in model attribite
     *
     * @param  string $attribute
     * @return App\Services\Countdown $countdown
     */
    public function until($attribute, $future = false)
    {
        $carbon = new Carbon;
        $timezone = config('app.timezone');
        $countdown = new Countdown($timezone, $carbon);
        if ($future) {
            $countdown->future();
        }
        $attribute = $this->{$attribute};
        $now = Carbon::now();

        return $countdown->from($now)
            ->to($attribute)->get();
    }

    /**
     * Return true/false depending on wether the timer is active based in model attribite
     *
     * @param  string $attribute
     * @return App\Services\Countdown $countdown
     */
    public function timer_active($attribute, $future = false)
    {
        return $this->until($attribute, $future)->isActive();
    }
}
