<?php

namespace App\Models\v1;

use App\Traits\Notifiable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MailingList extends Model
{
    use HasFactory;
    use Notifiable;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'city',
        'state',
        'zip',
        'country',
        'ip',
        'user_agent',
        'referrer',
    ];

    public function name(): Attribute
    {
        return new Attribute(
            get: fn ($value) => $value,
            set: fn ($value) => $value ?? str($this->email)->before('@'),
        );
    }

    /**
     * Route notifications for the mail channel.
     *
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return array|string
     */
    public function routeNotificationForMail($notification)
    {
        // Return email address and name...
        return [$this->email => $this->name];
    }
}