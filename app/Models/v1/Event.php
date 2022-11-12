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
<<<<<<< HEAD
        'details',
=======
        'description',
>>>>>>> 74877e1d6e74e818d4c6bd2d6d77ae7b1bd4ac0d
        'start_date',
        'end_date',
        'color',
        'user_id',
        'company_id',
<<<<<<< HEAD
        'company_type',
        'eventable_type',
        'eventable_id',
        'duration',
        'bgcolor',
        'location',
        'notify',
=======
        'eventable_type',
        'eventable_id',
>>>>>>> 74877e1d6e74e818d4c6bd2d6d77ae7b1bd4ac0d
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
<<<<<<< HEAD
        'notify' => 'boolean',
=======
>>>>>>> 74877e1d6e74e818d4c6bd2d6d77ae7b1bd4ac0d
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
<<<<<<< HEAD
     * Get the parent company model (Company or GiftShop).
     */
    public function company()
    {
        return $this->morphTo('company');
=======
     * Get the company that owns the event.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
>>>>>>> 74877e1d6e74e818d4c6bd2d6d77ae7b1bd4ac0d
    }

    /**
     * Get the eventable model that owns the event.
     */
    public function eventable(): MorphTo
    {
        return $this->morphTo();
    }
}
