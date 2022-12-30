<?php

namespace App\Models\v1;

use App\Services\AppInfo;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use ToneflixCode\LaravelFileable\Media;

class Image extends Model
{
    use HasFactory;

    protected $fillable = [
        'model',
        'meta',
        'file',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'meta' => 'collection',
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
            if (! $item->imageable instanceof Album &&
                ! $item->imageable instanceof VisionBoard &&
                ! $item->imageable instanceof Verification &&
                ! $item->imageable instanceof PortfolioPage) {
                $item->file = (new Media)->save('default', 'file', $item->file);
            } else {
                $item->file = (new Media)->save('private.images', 'file', $item->file);
            }
            if (! $item->file) {
                unset($item->file);
            }
            if (! $item->meta) {
                unset($item->meta);
            }
        });

        static::deleted(function ($item) {
            if (! $item->imageable instanceof Album &&
                ! $item->imageable instanceof VisionBoard &&
                ! $item->imageable instanceof Verification &&
                ! $item->imageable instanceof PortfolioPage) {
                (new Media)->delete('default', $item->file);
            } else {
                (new Media)->delete('private.images', $item->file);
            }
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
     * Get posibly protected URL of the image.
     *
     * @return string
     */
    protected function imageUrl(): Attribute
    {
        return Attribute::make(
            get: function () {
                // $wt = config('app.env') === 'local' ? '?wt='.Auth::user()->window_token : '?ctx='.rand();
                if (! $this->imageable instanceof Album &&
                    ! $this->imageable instanceof VisionBoard &&
                    ! $this->imageable instanceof Verification &&
                    ! $this->imageable instanceof PortfolioPage) {
                    return (new Media)->getMedia('default', $this->file);
                }
                $wt = '?preload=true';

                $superLoad = ($this->imageable instanceof Verification && $this->imageable->concierge_id === Auth::id()) ||
                    (Auth::user() ? Auth::user()->role === 'admin' : false);

                if ($superLoad) {
                    $wt = '?preload=true&wt='.Auth::user()->window_token;
                } elseif ($this->imageable && $this->imageable->user->id === (Auth::user() ? Auth::user()->id : 0)) {
                    $wt = '?preload=true&wt='.$this->imageable->user->window_token;
                }

                $wt .= '&ctx='.rand();
                $wt .= '&build='.AppInfo::basic()['version'] ?? '1.0.0';
                $wt .= '&mode='.config('app.env');
                $wt .= '&pov='.md5($this->src);

                return (new Media)->getMedia('private.images', $this->file).$wt;
            },
        );
    }

    /**
     * Get a shared/public URL of the image.
     *
     * @return string
     */
    protected function sharedImageUrl(): Attribute
    {
        return Attribute::make(
            get: function () {
                // $wt = config('app.env') === 'local' ? '?wt='.Auth::user()->window_token : '?ctx='.rand();
                if (! $this->imageable instanceof Album &&
                    ! $this->imageable instanceof VisionBoard &&
                    ! $this->imageable instanceof Verification &&
                    ! $this->imageable instanceof PortfolioPage) {
                    return (new Media)->getMedia('default', $this->file);
                }

                $wt = '?preload=true&shared&wt='.(Auth::user() ? Auth::user()->window_token : rand());
                $wt .= '&ctx='.rand();
                $wt .= '&build='.AppInfo::basic()['version'] ?? '1.0.0';
                $wt .= '&mode='.config('app.env');
                $wt .= '&pov='.md5($this->file);

                return (new Media)->getMedia('private.images', $this->file).$wt;
            },
        );
    }
}