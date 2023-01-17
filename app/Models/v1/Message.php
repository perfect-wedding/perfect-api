<?php

namespace App\Models\v1;

use Lexx\ChatMessenger\Models\Message as ChatMessenger;

class Message extends ChatMessenger
{
    protected $casts = [
        'data' => 'array',
    ];
}
