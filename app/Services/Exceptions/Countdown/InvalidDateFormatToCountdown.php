<?php

namespace App\Services\Exceptions\Countdown;

use InvalidArgumentException;

class InvalidDateFormatToCountdown extends InvalidArgumentException
{
    protected $message = 'Invalid date string or object to parse.';
}