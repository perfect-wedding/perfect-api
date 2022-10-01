<?php

namespace App\Models\v1;

use App\Services\AppInfo;
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
            $item->src = (new Media)->save('private.images', 'file', $item->src);
            if (! $item->src) {
                unset($item->src);
            }
            if (! $item->meta) {
                unset($item->meta);
            }
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
        return Attribute::make(
            get: function () {
                // $wt = config('app.env') === 'local' ? '?wt='.Auth::user()->window_token : '?ctx='.rand();
                $wt = '?preload=true';
                if ($this->imageable instanceof Verification && $this->imageable->concierge_id === Auth::id()) {
                    $wt = '?preload=true&wt='.Auth::user()->window_token;
                } elseif ($this->imageable && $this->imageable->user->id === Auth::user()->id || Auth::user()->role === 'admin') {
                    $wt = '?preload=true&wt='.$this->imageable->user->window_token;
                }

                $wt .= '&ctx='.rand();
                $wt .= '&build='.AppInfo::basic()['version'] ?? '1.0.0';
                $wt .= '&mode='.config('app.env');
                $wt .= '&pov='.md5($this->src);

                return (new Media)->image('private.images', $this->src).$wt;
            },
        );
    }

    /**
     * Get the URL to the fruit bay category's photo.
     *
     * @return string
     */
    protected function sharedImageUrl(): Attribute
    {
        return Attribute::make(
            get: function () {
                // $wt = config('app.env') === 'local' ? '?wt='.Auth::user()->window_token : '?ctx='.rand();
                $wt = '?preload=true&shared&wt='.Auth::user()->window_token;
                $wt .= '&ctx='.rand();
                $wt .= '&build='.AppInfo::basic()['version'] ?? '1.0.0';
                $wt .= '&mode='.config('app.env');
                $wt .= '&pov='.md5($this->src);

                return (new Media)->image('private.images', $this->src).$wt;
            },
        );
    }
}