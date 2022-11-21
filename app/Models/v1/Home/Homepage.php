<?php

namespace App\Models\v1\Home;

use App\Models\v1\Navigation;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use ToneflixCode\LaravelFileable\Traits\Fileable;

class Homepage extends Model
{
    use HasFactory, Fileable;

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'scrollable' => 'boolean',
        'default' => 'boolean',
        'landing' => 'boolean',
    ];

    protected $attributes = [
        'template' => 'Landing/AboutLayout',
        'details' => '',
    ];

    public function registerFileable(): void
    {
        $this->fileableLoader([
            'banner' => 'banner',
            'video' => 'banner',
        ]);
    }

    public static function registerEvents()
    {
        static::creating(function ($item) {
            $slug = str($item->title)->slug();
            $item->slug = (string) Homepage::whereSlug($slug)->exists() ? $slug->append(rand()) : $slug;
        });

        static::deleting(function ($item) {
            $item->slides()->delete();
            $item->content()->delete();
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

    /**
     * Get all of the clients for the Homepage
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function clients(): HasMany
    {
        return $this->hasMany(HomepageService::class, 'parent')->isType('client');
    }

    /**
     * Get all of the content for the Homepage
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function content(): HasMany
    {
        return $this->hasMany(HomepageContent::class, 'homepage_id');
    }

    public function details(): Attribute
    {
        return new Attribute(
            get: fn ($value) => $value ?? '',
        );
    }

    /**
     * Get all of the features for the Homepage
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function features(): HasMany
    {
        return $this->hasMany(HomepageService::class, 'parent')->isType('feature');
    }

    /**
     * Get the navigations for the company.
     *
     */
    public function navigations(): MorphMany
    {
        return $this->morphMany(Navigation::class, 'navigable');
    }

    /**
     * Get all of the slides for the Homepage
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function slides(): HasMany
    {
        return $this->hasMany(HomepageSlide::class, 'homepage_id');
    }

    public function template(): Attribute
    {
        return new Attribute(
            get: fn ($value) => $value ?? 'Landing/AboutLayout',
        );
    }
}
