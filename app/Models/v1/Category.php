<?php

namespace App\Models\v1;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Searchable\Searchable;
use Spatie\Searchable\SearchResult;
use ToneflixCode\LaravelFileable\Traits\Fileable;

class Category extends Model implements Searchable
{
    use HasFactory, Fileable;

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'image_url',
        'stats',
    ];

    public function registerFileable()
    {
        $this->fileableLoader('image', 'default', true);
    }

    public static function registerEvents()
    {
        static::creating(function ($cat) {
            $slug = Str::of($cat->title)->slug();
            $cat->slug = (string) Category::whereSlug($slug)->exists() ? $slug->append(rand()) : $slug;
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
     * Get all of the giftshops for the Category
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function shop_items(): HasMany
    {
        return $this->hasMany(ShopItem::class, 'category_id');
    }

    /**
     * Get the URL to the fruit bay category's photo.
     *
     * @return string
     */
    protected function imageUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->files['image'] ?? '',
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
                $shop_query = $this->shop_items()->shopActive();

                if (request()->has('company_stats') && request()->has('company')) {
                    $services_query->whereHas('company', function ($q) {
                        $q->where('id', request()->company);
                        $q->orWhere('slug', request()->company);
                    });
                    $inventories_query->whereHas('company', function ($q) {
                        $q->where('id', request()->company);
                        $q->orWhere('slug', request()->company);
                    });
                    $shop_query->whereHas('shop', function ($q) {
                        $q->where('id', request()->company);
                        $q->orWhere('slug', request()->company);
                    });
                }

                $inventories = $inventories_query->count();
                $shop_items = $shop_query->count();
                $services = $services_query->count();

                return [
                    'items' => $inventories + $services + $shop_items,
                    'services' => $services,
                    'companies' => $this->companies->count(),
                    'giftshops' => $shop_items,
                    'shop_items' => $shop_items,
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
        if ($type === 'giftshop') {
            return $query->whereHas('shop_items', function ($query) {
                $query->shopActive();
            });
        }

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