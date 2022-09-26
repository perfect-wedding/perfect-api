<?php

namespace App\Models\v1;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use ToneflixCode\LaravelFileable\Traits\Fileable;

class Bulletin extends Model
{
    use HasFactory, Fileable;

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'audience' => 'array',
        'expires_at' => 'datetime',
        'active' => 'boolean',
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array
     */
    protected $attributes = [
        'audience' => '[]',
    ];

    /**
     * Retrieve the model for a bound value.
     *
     * @param mixed $value
     * @param string|null $field
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function resolveRouteBinding($value, $field = null)
    {
        return $this->where('id', $value)
            ->orWhere('slug', $value)
            ->firstOrFail();
    }

    public function registerFileable()
    {
        $this->fileableLoader([
            'media' => 'banner',
            'thumbnail' => 'thumb',
        ]);
    }

    public static function registerEvents()
    {
        static::creating(function ($item) {
            $slug = str($item->title)->slug();
            $item->slug = (string) Bulletin::whereSlug($slug)->exists() ? $slug->append(rand()) : $slug;
        });
    }

    /**
     * Get for only active bulletins.
     *
     * @return void
     */
    function scopeActive($query)
    {
        $query->where('active', true);
    }

    /**
     * Get for only non expired bulletins.
     *
     * @return void
     */
    function scopeNotExpired($query)
    {
        $query->where('expires_at', '>=', now())
            ->orWhereNull('expires_at');
    }

    function scopeAudience($query, $audience)
    {
        $map_audience = [
            "vendor" => "vendor",
            "warehouse" => "warehouse",
            "concierge" => "concierge",
            "provider" => "provider",
            "all" => "all"
        ];

        $audience = collect($audience)->map(function ($item) use ($map_audience) {
            $item = $map_audience[$item] ?? $item;
            return $item;
        })->toArray();

        if (is_array($audience)) {
            if (in_array('all', $audience)) {
                return $query;
            }

            foreach ($audience as $key => $value) {
                if ($key === 0) {
                    $query->whereJsonContains('audience', $value);
                } else {
                    $query->orWhereJsonContains('audience', $value);
                }
            }
        } else {
            if ($audience === 'all') {
                return $query;
            }
            $query->whereJsonContains('audience', $audience);
        }
    }
}
