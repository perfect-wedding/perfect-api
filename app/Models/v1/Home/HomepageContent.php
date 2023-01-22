<?php

namespace App\Models\v1\Home;

use HaydenPierce\ClassFinder\ClassFinder;
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
        'content_type',
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

                $_model = collect(ClassFinder::getClassesInNamespace('App\\Models\\v1', ClassFinder::RECURSIVE_MODE));
                $instance = app($_model->filter(fn ($n) => str($n)->endsWith($attached))->first());

                $model = $instance->where('id', '!=', null);
                if (str($attached)->lower()->is('homepageservice')) {
                    $model->isType(null);
                }

                if ($this->content_type) {
                    if (str($attached)->lower()->is('category')) {
                        $model->where('type', $this->content_type);
                    }
                }

                $_resrc = collect(ClassFinder::getClassesInNamespace('App\Http\Resources\v1', ClassFinder::RECURSIVE_MODE));

                // Find the resource for the attached model
                $attached_rsc_name = str($attached)->remove('Homepage', false)->append('Collection');
                $collection = $_resrc->filter(
                    fn ($n) => str($n)->endsWith($attached_rsc_name) && ! str($n)->contains(['Business/Service'])
                )->first();

                $attachment = $model->get();
                if ($collection) {
                    $attachment = new $collection($attachment);
                }

                $key = str($attached)->remove('homepage', false)->lower()->plural()->toString();

                return [$key => $attachment];
            })),
        );
    }

    public function attachedModelsOnly(): Attribute
    {
        return new Attribute(
            get: fn () => (collect($this->attached)->map(function ($attached) {
                $_model = collect(ClassFinder::getClassesInNamespace('App\\Models\\v1', ClassFinder::RECURSIVE_MODE));
                $instance = app($_model->filter(fn ($n) => str($n)->endsWith($attached))->first());
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