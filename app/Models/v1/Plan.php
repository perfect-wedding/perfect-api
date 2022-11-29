<?php

namespace App\Models\v1;

use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;
use ToneflixCode\LaravelFileable\Traits\Fileable;

class Plan extends Model
{
    use HasFactory, Fileable;

    public $appends = [
        'cover_url',
        'tenure',
        'duration',
    ];

    protected $casts = [
        'meta' => AsCollection::class,
        'split' => 'array',
        'annual' => 'boolean',
        'popular' => 'boolean',
        'features' => 'array',
        'duration' => 'integer',
        'trial_days' => 'integer',
    ];

    protected $attributes = [
        'trial_days' => 0,
    ];

    public function registerFileable()
    {
        $this->fileableLoader([
            'cover' => 'banner',
        ]);
    }

    public static function registerEvents()
    {
        parent::boot();

        static::saving(function ($model) {
            $model->trial_days = $model->trial_days ?? 0;
            $model->split = $model->split ?? (object)[];
            $slug = Str::of($model->title)->slug();
            $model->slug = (string) Plan::whereSlug($slug)->exists() ? $slug->append(rand()) : $slug;
        });
    }

    /**
     * Get the URL to the fruit bay item's photo.
     *
     * @return string
     */
    protected function coverUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->images['cover'] ?? $this->default_image,
        );
    }

    protected function duration(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $this->annual ? 365 : $value,
            set: fn ($value) => $this->annual ? 365 * $value : $value,
        );
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

    public function scopeFeatureable($query)
    {
        return $query->where('type', 'featured');
    }

    public function scopeFeatureableType($query, $type = 'company')
    {
        return $query->where('type', 'featured')->where('meta->type', $type);
    }

    protected function tenure(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $this->annual ? 'days' : $value,
            set: fn ($value) => $this->annual ? 'days' : $value,
        );
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
                    $query->whereJsonContains('meta->places', $value);
                } else {
                    $query->orWhereJsonContains('meta->places', $value);
                }
                $query->orWhere('meta->places->' . $value, '1');
                $query->orWhere('meta->places->' . $value, 1);
                $query->orWhere('meta->places->' . $value, true);
            }
        } else {
            if ($places === 'all') {
                return $query;
            }
            $query->whereJsonContains('meta->places', $places);
            $query->orWhere('meta->places->' . $places, '1');
            $query->orWhere('meta->places->' . $places, 1);
            $query->orWhere('meta->places->' . $places, true);
        }

        $query->orWhereJsonContains('meta->places->all', 1);
    }

    /**
     * Get all of the transactions for the Plan
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function transactions(): MorphMany
    {
        return $this->morphMany(Transaction::class, 'transactable')->restricted();
    }
}
