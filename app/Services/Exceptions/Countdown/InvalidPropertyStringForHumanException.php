<?php

namespace App\Services\Exceptions\Countdown;

use InvalidArgumentException;

class InvalidPropertyStringForHumanException extends InvalidArgumentException
{
    protected $message = 'String to parse for human contains invalid property';
}
