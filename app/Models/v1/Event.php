<?php

namespace App\Models\v1;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Event extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'title',
        'details',
        'start_date',
        'end_date',
        'color',
        'user_id',
        'company_id',
        'company_type',
        'eventable_type',
        'eventable_id',
        'duration',
        'bgcolor',
        'location',
        'notify',
        'meta',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'meta' => 'collection',
        'notify' => 'boolean',
    ];

    protected static function booted()
    {
        static::creating(function ($event) {
            $slug = str($event->title)->slug();
            $event->slug = (string) Event::whereSlug($slug)->exists() ? $slug->append(rand()) : $slug;
        });
    }

    /**
     * Get the parent company model (Company or GiftShop).
     */
    public function company()
    {
        return $this->morphTo('company');
    }

    /**
     * Get the eventable model that owns the event.
     */
    public function eventable(): MorphTo
    {
        return $this->morphTo();
    }

    public function meta(): Attribute
    {
        return new Attribute(
            get: fn ($value) => $value ? collect(json_decode($value)) : new \stdClass
        );
    }

    /**
     * Get the user that owns the event.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}