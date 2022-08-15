<?php

namespace App\Models\v1;

use App\Services\Media;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Category extends Model
{
    use HasFactory;

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'image_url',
        'stats',
    ];

    protected static function booted()
    {
        static::creating(function ($cat) {
            $slug = Str::of($cat->name)->slug();
            $cat->slug = (string) Category::whereSlug($slug)->exists() ? $slug->append(rand()) : $slug;
        });

        static::saving(function ($cat) {
            $cat->image = (new Media)->save('default', 'image', $cat->image);
        });

        static::deleted(function ($cat) {
            (new Media)->delete('default', $cat->image);
        });
    }

    // public function companiesx(): Attribute
    // {
    //     return new Attribute(
    //         get: fn () => $this->services->load('company')->pluck('company'),
    //     );
    // }

    /**
     * Get all of the reviews for the company.
     */
    public function companies(): Attribute
    {
        return new Attribute(
            get: fn () => Company::whereHas('services', function ($q) {
                $q->where('category_id', $this->id);
            }),
        );
    }

    /**
     * Get all of the services for the Category
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    /**
     * Get all of the inventories for the Category
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function inventories(): HasMany
    {
        return $this->hasMany(Inventory::class);
    }

    /**
     * Get the URL to the fruit bay category's photo.
     *
     * @return string
     */
    protected function imageUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => (new Media)->image('default', $this->image),
        );
    }

    /**
     * Get stats for the category.
     *
     * @return string
     */
    protected function stats(): Attribute
    {
        return Attribute::make(
            get: fn () => [
                'companies' => $this->companies->count(),
                'inventories' => $this->inventories()->count(),
                'services' => $this->services()->count(),
                'items' => $this->inventories()->count() + $this->services()->count(),
            ],
        );
    }

    /**
     * Get stats for the category.
     *
     * @return string
     */
    public function loadStats()
    {
        return[
            'companies' => Company::whereIn('id', function ($q) {
                $q->select(DB::raw("company_id from services where category_id = {$this->id}"));
            })->count(),
        ];
    }
}
