<?php

namespace App\Models\v1;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class PasswordCodeResets extends Model
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'email',
        'code',
        'created_at',
    ];
}
