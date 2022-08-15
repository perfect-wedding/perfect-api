<?php

namespace App\Models\v1;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Plan extends Model
{
    use HasFactory;

    public $fillable = [
        'duration',
        'max_contacts',
        'price',
        'slug',
        'status',
        'tenure',
        'title',
        'trial',
    ];

    public $appends = [
        'image_url',
    ];

    public static function boot()
    {
        parent::boot();
        static::saving(function ($plan) {
            $slug = Str::of($plan->title)->slug();
            $plan->slug = (string) Plan::whereSlug($slug)->exists() ? $slug->append(rand()) : $slug;
        });
    }

    /**
     * Get the URL to the fruit bay item's photo.
     *
     * @return string
     */
    protected function imageUrl(): Attribute
    {
        $image = $this->image
            ? img($this->image, 'banner', 'large')
            : 'https://loremflickr.com/320/320/'.urlencode($this->title ?? 'fruit');

        return Attribute::make(
            get: fn () => $image,
        );
    }
}
