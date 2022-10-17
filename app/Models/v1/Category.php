<?php

namespace App\Models\v1;

use App\Services\Media;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Searchable\Searchable;
use Spatie\Searchable\SearchResult;

class Category extends Model implements Searchable
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

    /**
     * Retrieve the model for a bound value.
     *
     * @param  mixed  $value
     * @param  string|null  $field
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function resolveRouteBinding($value, $field = null)
    {
        return $this->where('id', $value)
            ->orWhere('slug', $value)
            ->firstOrFail();
    }

    // public function companiesx(): Attribute
    // {
    //     return new Attribute(
    //         get: fn () => $this->services->load('company')->pluck('company'),
    //     );
    // }

    public function getSearchResult(): SearchResult
    {
        return new \Spatie\Searchable\SearchResult(
            $this,
            $this->title,
            $this->slug
        );
    }

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
     * Get all of the reviews for the company.
     */
    public function warehouseCompanies(): Attribute
    {
        return new Attribute(
            get: fn () => Company::whereHas('inventories', function ($q) {
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
            get: function () {
                $inventories_query = $this->inventories()->ownerVerified();
                $services_query = $this->services()->ownerVerified();

                if (request()->has('company_stats') && request()->has('company')) {
                    $services_query->whereHas('company', function($q) {
                        $q->where('id', request()->company);
                        $q->orWhere('slug', request()->company);
                    });
                    $inventories_query->whereHas('company', function($q) {
                        $q->where('id', request()->company);
                        $q->orWhere('slug', request()->company);
                    });
                }

                $inventories = $inventories_query->count();
                $services = $services_query->count();

                return [
                    'items' => $inventories + $services,
                    'services' => $services,
                    'companies' => $this->companies->count(),
                    'inventories' => $inventories,
                ];
            },
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

    public function scopeOwnerVerified($query, $type = 'warehouse')
    {
        return $query->whereHas($type == 'warehouse' ? 'inventories' : 'services', function ($query) {
            $query->whereHas('company', function ($q) {
                $q->verified();
            });
        });
    }

    public function scopeForCompany($query, $company, $type = 'warehouse')
    {
        return $query->whereHas($type == 'warehouse' ? 'inventories' : 'services', function ($query) use ($company) {
            $query->whereHas('company', function ($q) use ($company) {
                $q->where('slug', $company)
                  ->orWhere('id', $company);
            });
        });
    }
}
