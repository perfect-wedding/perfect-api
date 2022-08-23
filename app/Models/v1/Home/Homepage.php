<?php

namespace App\Models\v1\Home;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Homepage extends Model
{
    use HasFactory;

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'scrollable' => 'boolean',
        'default' => 'boolean',
    ];

    protected static function booted()
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
     * Get all of the slides for the Homepage
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function slides(): HasMany
    {
        return $this->hasMany(HomepageSlide::class, 'homepage_id');
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
}
