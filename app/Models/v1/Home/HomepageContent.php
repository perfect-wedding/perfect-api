<?php

namespace App\Models\v1\Home;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use ToneflixCode\LaravelFileable\Traits\Fileable;

class HomepageContent extends Model
{
    use HasFactory, Fileable;

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'attached' => 'array',
        'linked' => 'boolean',
        'iterable' => 'boolean',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'title',
        'subtitle',
        'leading',
        'content',
        'parent',
        'linked',
        'iterable',
        'attached',
        'template',
    ];

    protected $attributes = [
        'attached' => '{}',
    ];

    public function registerFileable(): void
    {
        $this->fileableLoader([
            'image' => 'default',
            'image2' => 'default',
            'image3' => 'default',
        ]);
    }

    public static function registerEvents()
    {
        static::creating(function ($item) {
            $slug = str($item->title)->slug();
            $item->slug = (string) HomepageContent::whereSlug($slug)->exists() ? $slug->append(rand()) : $slug;
        });
    }

    /**
     * Get the page that owns the HomepageSlide
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function page(): BelongsTo
    {
        return $this->belongsTo(Homepage::class, 'homepage_id');
    }

    public function attachedModel(): Attribute
    {
        return new Attribute(
            get: fn () => (collect($this->attached)->mapWithKeys(function ($attached) {
                $instance = app('App\\Models\\v1\\Home\\'.ucfirst($attached));
                $model = $instance->where('id', '!=', null);
                if (strtolower($attached) === 'homepageservice') {
                    $model->isType(null);
                }
                $attach = $model->get();
                $collection = str($attached)
                    ->remove('homepage', false)->ucfirst()
                    ->append('Collection')
                    ->prepend('App\Http\Resources\v1\Home\\')->toString();
                if (class_exists($collection)) {
                    $attach = (new $collection($attach));
                }

                $key = str($attached)->remove('homepage', false)->lower()->plural()->toString();

                return [$key => $attach];
            })),
        );
    }

    public function attachedModelsOnly(): Attribute
    {
        return new Attribute(
            get: fn () => (collect($this->attached)->map(function ($attached) {
                $instance = app('App\\Models\\v1\\Home\\'.ucfirst($attached));
                $model = $instance->where('id', '!=', null);
                if (strtolower($attached) === 'homepageservice') {
                    $model->isType(null);
                }

                return $model->get();
            })),
        );
    }

    public function content(): Attribute
    {
        return new Attribute(
            get: fn ($value) => $value ?? '',
        );
    }
}
