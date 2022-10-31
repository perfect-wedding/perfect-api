<?php

namespace App\Models\v1;

use App\Traits\Meta;
use App\Traits\Notifiable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use ToneflixCode\LaravelFileable\Media;
use ToneflixCode\LaravelFileable\Traits\Fileable;

class GiftShop extends Model
{
    use HasFactory, Fileable, Meta, Notifiable;

    protected $fillable = [
        'email',
        'name',
        'merchant_name',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'socials' => 'array',
        'active' => 'boolean',
    ];

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

    public function registerFileable()
    {
        $this->fileableLoader('image', 'logo');
    }

    public static function registerEvents()
    {
        static::creating(function ($item) {
            $slug = str($item->name)->slug();
            $item->slug = (string) GiftShop::whereSlug($slug)->exists() ? $slug->append(rand()) : $slug;
            $item->invite_code = mt_rand(100000, 999999);
        });

        static::deleting(function ($item) {
            $item->items()->delete();
            $item->reviews()->delete();
        });
    }

    /**
     * Get all of the items for the GiftShop
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function items(): HasMany
    {
        return $this->hasMany(ShopItem::class);
    }

    /**
     * Get the URL to the GiftShop's logo.
     *
     * @return string
     */
    protected function logoUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => (new Media)->getMedia('logo', $this->image),
        );
    }

    /**
     * Get all of the GiftShop's orders.
     */
    public function orders()
    {
        return $this->morphMany(Order::class, 'company');
    }

    /**
     * Relationship for models that reviewed this GiftShop.
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
     * Get all of the GiftShop's reviews.
     */
    public function reviews()
    {
        return $this->morphMany(Review::class, 'reviewable');
    }

    /**
     * Route notifications for the mail channel.
     *
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return array|string
     */
    public function routeNotificationForMail()
    {
        // Return email address and name...
        return [$this->email => $this->merchant_name];
    }

    /**
     * Get the GiftShop's stats.
     *
     * @return string
     */
    protected function stats(): Attribute
    {
        return Attribute::make(
            get: fn () => [
                'orders' => $this->orders()->count(),
                'items' => $this->items()->count(),
                'reviews' => $this->reviews()->count(),
                'rating' => $this->reviews()->count() > 0 ? round($this->reviews()->pluck('rating')->avg(), 1) : 0.0,
            ],
        );
    }

    /**
     * Get all of the transactions for the GiftShop
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function transactions(): MorphMany
    {
        return $this->morphMany(Transaction::class, 'transactable')->flexible();
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function scopeAccepted($query)
    {
        return $query->where('invite_code', null);
    }
}
