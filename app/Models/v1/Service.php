<?php

namespace App\Models\v1;

use App\Services\Media;
use App\Traits\Appendable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use ToneflixCode\LaravelFileable\Traits\Fileable;

class Service extends Model
{
    use HasFactory, Appendable, Fileable;

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
        $this->fileableLoader([
            'image' => 'default',
        ]);
    }

    public static function registerEvents()
    {
        static::creating(function ($item) {
            $slug = str($item->title)->slug();
            $item->slug = (string) Service::whereSlug($slug)->exists() ? $slug->append(rand()) : $slug;
        });

        static::deleting(function ($item) {
            $item->offers()->delete();
            $item->reviews()->delete();
        });
    }

    /**
     * Get the company that owns the Service
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the category that owns the Service
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the URL to the fruit bay category's photo.
     *
     * @return string
     */
    protected function imageUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->images['image'] ?? '',
        );
    }

    /**
     * Get all of the service's offers.
     */
    public function offers()
    {
        return $this->morphMany(Offer::class, 'offerable');
    }

    /**
     * Get all of the service's orders.
     */
    public function orders()
    {
        return $this->morphMany(Order::class, 'orderable');
    }

    /**
     * Get all of the service's order requests.
     */
    public function orderRequests()
    {
        return $this->morphMany(OrderRequest::class, 'orderable');
    }

    /**
     * Order the results by orders.
     */
    public function orderByOrders()
    {
        return $this->orderByDesc(function ($q) {
            $q->select([DB::raw('count(orders.id) oc from orders')])
                ->where([['orderable_type', Service::class], ['orderable_id', DB::raw('services.id')]]);
        });
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
     * Get the category's stats.
     *
     * @return string
     */
    protected function stats(): Attribute
    {
        $reviews = $this->reviews();

        return Attribute::make(
            get: fn () => [
                'orders' => $this->orders()->count(),
                'offers' => $this->offers()->count(),
                'reviews' => $reviews->count(),
                'rating' => $reviews->count() > 0 ? round($this->reviews()->pluck('rating')->avg(), 1) : 0.0,
            ],
        );
    }

    /**
     * Get all of the transactions for the Service
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function transactions(): MorphMany
    {
        return $this->morphMany(Transaction::class, 'transactable')->flexible();
    }

    /**
     * Get the user that owns the Service
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}