<?php

namespace App\Models\v1\Home;

use App\Traits\Imageable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HomepageContent extends Model
{
    use HasFactory, Imageable;

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

    protected $attributes = [
        'attached' => '{}',
    ];

    public function registerImageable()
    {
        $this->imageableLoader([
            'image' => 'default',
            'image2' => 'default'
        ]);
    }

    public static function registerEvents()
    {
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
            get: fn () => (collect($this->attached)->mapWithKeys(function($attached) {
                $model = app("App\\Models\\v1\\Home\\".ucfirst($attached));
                $attach = $model->where('id', '!=', NUll)->get();
                $collection = str($attached)
                    ->remove('homepage', false)->ucfirst()->append('Collection')->prepend('App\Http\Resources\v1\Home\\')->toString();
                if (class_exists($collection)) {
                    $attach = (new $collection($attach));
                }

                $key = str($attached)->remove('homepage', false)->lower()->plural()->toString();
                return [$key => $attach];
            })),
        );
    }
}