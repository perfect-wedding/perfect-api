<?php

namespace App\Models\v1;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Featured extends Model
{
    use HasFactory;

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'meta' => 'collection',
        'places' => 'collection',
        'active' => 'boolean',
        'recurring' => 'boolean',
        'pending' => 'boolean',
    ];

    /**
     * The model's fillable attributes.
     *
     * @var array
     */
    protected $fillable = [
        'featureable_id',
        'featureable_type',
    ];

    /**
     * Get the parent featureable model (Company, Service or Inventory).
     */
    public function featureable()
    {
        return $this->morphTo('featureable');
    }

    public function meta(): Attribute
    {
        return new Attribute(
            get: fn ($value) => collect(json_decode($value ?? '[]', JSON_FORCE_OBJECT))->map(function($item) {
                // Convert all true and false strings to boolean
                if (in_array($item, ['true', 'false'])) {
                    return $item === 'true';
                }
                return $item;
            }),
        );
    }

    /**
     * Get the plan that owns the Featured item
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * Scope to return featured items based on wether they are pending or not.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function scopePending($query, $pending = true)
    {
        return $query->where('pending', $pending);
    }

    /**
     * Scope to return only active featured items
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function scopeActive($query)
    {
        // Calculate the days between the current date and the created date against the featured duration depending on wether the tenure is hourly, daily, weekly, monthly or yearly and wether the featured is active or not.
        $query->whereRaw('
            DATEDIFF(CURRENT_DATE(), created_at) <= duration *
            CASE
                WHEN tenure = "hourly" THEN 0.24
                WHEN tenure = "daily" THEN 1
                WHEN tenure = "weekly" THEN 7
                WHEN tenure = "monthly" THEN 30
                WHEN tenure = "yearly" THEN 365
            END'
        )->where('active', true);
    }

    public function scopePlace($query, $places)
    {
        $map_places = [
            'user' => 'user',
            'vendor' => 'vendor',
            'giftshop' => 'giftshop',
            'warehouse' => 'warehouse',
            'marketplace' => 'marketplace',
            'warehouse.dashboard' => 'warehouse.dashboard',
            'concierge.dashboard' => 'concierge.dashboard',
            'provider.dashboard' => 'provider.dashboard',
            // 'all' => 'all',
        ];

        $places = collect($places)->map(function ($item) use ($map_places) {
            return $map_places[$item] ?? $item;
        })->toArray();

        if (is_array($places)) {
            if (in_array('all', $places)) {
                return $query;
            }

            foreach ($places as $key => $value) {
                if ($key === 0) {
                    $query->whereJsonContains('places', $value);
                } else {
                    $query->orWhereJsonContains('places', $value);
                }
                $query->orWhere('places->' . $value, '1');
                $query->orWhere('places->' . $value, 1);
                $query->orWhere('places->' . $value, true);
            }
        } else {
            if ($places === 'all') {
                return $query;
            }
            $query->whereJsonContains('places', $places);
            $query->orWhere('places->' . $places, '1');
            $query->orWhere('places->' . $places, 1);
            $query->orWhere('places->' . $places, true);
        }

        $query->orWhereJsonContains('places', 'all');
    }
}
