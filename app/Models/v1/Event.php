<?php

namespace App\Models\v1;

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
        'description',
        'start_date',
        'end_date',
        'color',
        'user_id',
        'company_id',
        'eventable_type',
        'eventable_id',
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
    ];

    protected static function booted()
    {
        static::creating(function ($event) {
            $slug = str($event->title)->slug();
            $event->slug = (string) Event::whereSlug($slug)->exists() ? $slug->append(rand()) : $slug;
        });
    }

    /**
     * Get the user that owns the event.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the company that owns the event.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the eventable model that owns the event.
     */
    public function eventable(): MorphTo
    {
        return $this->morphTo();
    }
}
