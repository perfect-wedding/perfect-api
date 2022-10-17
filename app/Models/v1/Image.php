<?php

namespace App\Models\v1;

use App\Services\AppInfo;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use ToneflixCode\LaravelFileable\Traits\Fileable;

class Image extends Model
{
    use HasFactory, Fileable;

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

    public function registerFileable()
    {
        $this->fileableLoader(
            'file',
            $this->imageable instanceof Album || $this->imageable instanceof VisionBoard
                ? 'private.images'
                : 'default'
        );
    }

    public static function registerEvents()
    {
        static::saving(function ($item) {
            if (! $item->meta) {
                unset($item->meta);
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
     * Get the URL to the photo.
     *
     * @return string
     */
    protected function imageUrl(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (! $this->imageable instanceof Album && ! $this->imageable instanceof VisionBoard) {
                    return $this->files['file'] ?? '';
                }
                // $wt = config('app.env') === 'local' ? '?wt='.Auth::user()->window_token : '?ctx='.rand();
                $wt = '?preload=true';

                $superLoad = ($this->imageable instanceof Verification && $this->imageable->concierge_id === Auth::id()) ||
                    Auth::user()->role === 'admin';

                if ($superLoad) {
                    $wt = '?preload=true&wt='.Auth::user()->window_token;
                } elseif ($this->imageable && $this->imageable->user->id === Auth::user()->id) {
                    $wt = '?preload=true&wt='.$this->imageable->user->window_token;
                }

                $wt .= '&ctx='.rand();
                $wt .= '&build='.AppInfo::basic()['version'] ?? '1.0.0';
                $wt .= '&mode='.config('app.env');
                $wt .= '&pov='.md5($this->file);

                return $this->files['file'].$wt;
            },
        );
    }

    /**
     * Get the URL to the photo.
     *
     * @return string
     */
    protected function sharedImageUrl(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (! $this->imageable instanceof Album && ! $this->imageable instanceof VisionBoard) {
                    return $this->files['file'];
                }
                // $wt = config('app.env') === 'local' ? '?wt='.Auth::user()->window_token : '?ctx='.rand();
                $wt = '?preload=true&shared&wt='.Auth::user()->window_token;
                $wt .= '&ctx='.rand();
                $wt .= '&build='.AppInfo::basic()['version'] ?? '1.0.0';
                $wt .= '&mode='.config('app.env');
                $wt .= '&pov='.md5($this->file);

                return $this->files['file'].$wt;
            },
        );
    }
}
