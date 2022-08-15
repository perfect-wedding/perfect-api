<?php

namespace App\Models\v1;

use App\Services\Media;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Image extends Model
{
    use HasFactory;

    protected $fillable = [
        'model',
        'meta',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'meta' => 'array',
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array
     */
    protected $attributes = [
        'meta' => '{}',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'image_url',
    ];

    protected static function booted()
    {
        static::saving(function ($item) {
            $item->image = (new Media)->save('private.images', 'file', $item->image);
        });

        static::deleted(function ($item) {
            (new Media)->delete('private.images', $item->image);
        });
    }

    /**
     * Get the parent imageable model (album or vision board).
     */
    public function imageable()
    {
        return $this->morphTo();
    }

    /**
     * Get the URL to the fruit bay category's photo.
     *
     * @return string
     */
    protected function imageUrl(): Attribute
    {
        $wToken = config('app.env') === 'local' ? '?Window-Token='.Auth::user()->window_token : null;

        return Attribute::make(
            get: fn () => (new Media)->image('private.images', $this->image).$wToken,
        );
    }
}
