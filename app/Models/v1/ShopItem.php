<?php

namespace App\Models\v1;

use App\Traits\Appendable;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\Searchable\Searchable;
use Spatie\Searchable\SearchResult;
use ToneflixCode\LaravelFileable\Media;
use ToneflixCode\LaravelFileable\Traits\Fileable;

class ShopItem extends Model implements Searchable
{
    use HasFactory, Appendable, Fileable;

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'image_url',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'colors' => 'array',
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array
     */
    protected $attributes = [
        'colors' => '[]',
    ];

    public static function boot()
    {
        parent::boot();
        static::creating(function ($item) {
            $slug = str($item->name)->slug();
            $item->slug = (string) ShopItem::whereSlug($slug)->exists() ? $slug->append(rand()) : $slug;
        });
    }

    public function getSearchResult(): SearchResult
    {
        return new \Spatie\Searchable\SearchResult(
            $this,
            $this->name,
            $this->slug
        );
    }

    /**
     * Get the ID to ShopItem's GiftShop (Company).
     *
     * @return string
     */
    protected function Company(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->shop,
        );
    }

    /**
     * Get the ID to ShopItem's GiftShop (Company).
     *
     * @return string
     */
    protected function CompanyId(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->gift_shop_id,
        );
    }

    /**
     * Get all of the ShopItem's orders.
     */
    public function orders()
    {
        return $this->morphMany(Order::class, 'orderable');
    }

    /**
     * Relationship for models that reviewed this model.
     *
     * @param  null|\Illuminate\Database\Eloquent\Model  $model
     * @return mixed
     */
    public function reviewers()
    {
        return $this->morphToMany(User::class, 'reviewable', 'reviews', 'reviewable_id', 'user_id')
                    ->withTimestamps()
                    ->withPivot('rating');
    }

    /**
     * Get all of the service's reviews.
     */
    public function reviews()
    {
        return $this->morphMany(Review::class, 'reviewable');
    }

    /**
     * Get the ShopItem's stats.
     *
     * @return string
     */
    protected function stats(): Attribute
    {
        return Attribute::make(
            get: fn () => [
                'orders' => $this->orders()->count(),
                'reviews' => $this->reviews()->count(),
                'rating' => $this->reviews()->count() > 0 ? round($this->reviews()->pluck('rating')->avg(), 1) : 0.0,
            ],
        );
    }

    /**
     * Get all of the transactions for the ShopItem
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function transactions(): MorphMany
    {
        return $this->morphMany(Transaction::class, 'transactable')->flexible();
    }

    /**
     * Get the user that owns the ShopItem
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the GiftShop that owns the ShopItem
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function shop(): BelongsTo
    {
        return $this->belongsTo(GiftShop::class, 'gift_shop_id');
    }

    /**
     * Get the category that owns the ShopItem
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get all of the album's images.
     */
    public function images()
    {
        return $this->morphMany(Image::class, 'imageable');
    }

    /**
     * Get the URL to ShopItem's image.
     *
     * @return string
     */
    protected function imageUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->images[0]->image_url ?? (new Media)->getMedia('default', $this->image),
        );
    }

    public function scopeShopActive($query)
    {
        return $query->whereHas('shop', function ($query) {
            $query->active();
        });
    }

    /**
     * Scope the results ordered by relationsp.
     *
     * @return void
     */
    public function scopeOrderingBy($query, $type = 'top')
    {
        if ($type === 'top') {
            $query->withAvg('reviews', 'rating')->orderByDesc('reviews_avg_rating');
            $query->withCount('orders')->orderByDesc('orders_count');
        } elseif ($type === 'most-ordered') {
            $query->withCount('orders')->orderByDesc('orders_count');
        } elseif ($type === 'top-reviewed') {
            $query->withCount('reviews')->orderByDesc('reviews_count');
        }
    }
}
