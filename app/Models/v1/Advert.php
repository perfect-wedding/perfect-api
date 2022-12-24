<?php

namespace App\Models\v1;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use ToneflixCode\LaravelFileable\Traits\Fileable;

class Advert extends Model
{
    use HasFactory, Fileable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'title',
        'details',
        'url',
        'meta',
        'active',
        'places',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'meta' => 'collection',
        'places' => 'collection',
        'active' => 'boolean',
    ];

    public function registerFileable()
    {
        $this->fileableLoader([
            'media' => 'banner',
        ]);
    }

    public static function registerEvents()
    {
        static::creating(function ($item) {
            $slug = str($item->title)->slug();
            $item->slug = (string) Advert::whereSlug($slug)->exists() ? $slug->append(rand()) : $slug;
        });
    }

    /**
     * Retrieve the model for a bound value.
     *
     * @param  mixed  $value
     * @param  string|null  $field
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function resolveRouteBinding($value, $field = null)
    {
        return $this->where('id', $value)
            ->orWhere('slug', $value)
            ->firstOrFail();
    }

    public function meta(): Attribute
    {
        return new Attribute(
            get: fn ($value) => collect(json_decode($value ?? '[]', true))->map(function ($item) {
                // Convert all true and false strings to boolean
                if (in_array($item, ['true', 'false'])) {
                    return $item === 'true';
                }

                return $item;
            }),
        );
    }

    /**
     * Scope to return only active adverts
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
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
            }
        } else {
            if ($places === 'all') {
                return $query;
            }
            $query->whereJsonContains('places', $places);
        }

        $query->orWhereJsonContains('places', 'all');
    }
}
