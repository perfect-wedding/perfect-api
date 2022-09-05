<?php

namespace App\Services\Exceptions\Countdown;

use InvalidArgumentException;

class InvalidArgumentToCountdown extends InvalidArgumentException
{
    protected $message = 'You must at least tell where to count from.';
}