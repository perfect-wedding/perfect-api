<?php

namespace App\Models\v1;

use App\Services\Media;
use App\Traits\Notifiable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;
use Spatie\Searchable\Searchable;
use Spatie\Searchable\SearchResult;
use \Staudenmeir\EloquentHasManyDeep\HasRelationships;

class Company extends Model implements Searchable
{
    use HasFactory, Notifiable, HasRelationships;

    protected $fillable = [
        'name',
        'phone',
        'email',
        'type',
        'role',
        'intro',
        'about',
        'location',
        'address',
        'country',
        'state',
        'city',
        'logo',
        'postal',
        'banner',
        'verified_data',
        'rc_number',
        'rc_company_type',
    ];

    protected $appends = [
        'logo_url',
        'banner_url',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'featured_to' => 'datetime',
        'verified_data' => 'array',
        'location' => 'collection',
    ];

    protected static function booted()
    {
        static::creating(function ($company) {
            $slug = Str::of($company->name)->slug();
            $company->slug = (string) Company::whereSlug($slug)->exists() ? $slug->append(rand()) : $slug;
        });

        static::saving(function ($company) {
            $company->logo = (new Media)->save('logo', 'logo', $company->logo);
            $company->banner = (new Media)->save('banner', 'banner', $company->banner);
        });

        static::deleted(function ($company) {
            (new Media)->delete('logo', $company->logo);
            (new Media)->delete('banner', $company->banner);
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

    public function getSearchResult(): SearchResult
    {
        return new \Spatie\Searchable\SearchResult(
            $this,
            $this->name,
            $this->slug
        );
    }

    /**
     * Get the URL to the company's logo.
     *
     * @return string
     */
    protected function bannerUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => (new Media)->image('banner', $this->banner),
        );
    }

    public function customers(): Attribute
    {
        return new Attribute(
            get: fn () => User::whereHas('orders', function ($q) {
                $q->whereNot('user_id', auth()->user()->id);
                $q->where('company_id', $this->id);
                $q->where('company_type', Company::class);
            }),
        );
    }

    /**
     * Get the calendar events for the company.
     *
     */
    public function events(): MorphMany
    {
        return $this->morphMany(Event::class, 'company');
    }

    /**
     * Determin if the company is featured.
     *
     * @return string
     */
    protected function featured(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->featureds()->active()->exists(),
        );
    }

    /**
     * Get all of the featureds for the Company
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function featureds(): MorphMany
    {
        return $this->morphMany(Featured::class, 'featureable');
    }

    public function fullAddress(): Attribute
    {
        return new Attribute(
            get: fn () => collect([
                $this->address,
                $this->city,
                $this->state,
                $this->country,
            ])->filter()->implode(', '),
        );
    }

    /**
     * Get all of the inventories for the Company
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function inventories(): HasMany
    {
        return $this->hasMany(Inventory::class);
    }

    /**
     * Get the URL to the company's logo.
     *
     * @return string
     */
    protected function logoUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => (new Media)->image('logo', $this->logo),
        );
    }

    /**
     * Get all of the company's orders.
     */
    public function orders(): MorphMany
    {
        return $this->morphMany(Order::class, 'company');
    }

    /**
     * Get all of the order requests for the Company
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function orderRequests(): HasMany
    {
        return $this->hasMany(OrderRequest::class);
    }

    /**
     * Get all of the reviews for the company.
     */
    public function reviews()
    {
        return $this->hasManyDeep(
            Review::class,
            [Service::class],
            [null, ['reviewable_type', 'reviewable_id']]
        );
        // return Review::whereHasMorph('reviewable', Service::class, function ($q) {
        //     $q->where('company_id', $this->id);
        // });
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
        return [$this->user->email => $this->user->firstname];
    }

    /**
     * Route notifications for the twillio channel.
     *
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return array|string
     */
    public function routeNotificationForTwilio()
    {
        return $this->user->phone;
    }

    /**
     * Get all of the services for the Company
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    /**
     * Get all of the staff for the Company
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function staff(): HasMany
    {
        return $this->hasMany(CompanyStaff::class);
    }

    /**
     * Get the company's stats.
     *
     * @return string
     */
    protected function stats(): Attribute
    {
        $reviews = $this->reviews();

        return Attribute::make(
            get: fn () => [
                'sales' => $this->orders()->whereStatus('completed')->count(),
                'reviews' => $reviews->count(),
                'rating' => $reviews->count() > 0 ? round($this->reviews()->pluck('rating')->avg(), 1) : 0.0,
            ],
        );
    }

    /**
     * Get the verification status of the company.
     *
     * @return string
     */
    protected function status(): Attribute
    {
        return Attribute::make(
            get: fn () => ($this->verification->status === 'rejected'
                ? $this->verification->status
                : ($this->task ? $this->task->status : $this->verification->status)),
        );
    }

    /**
     * Get the currently active task for this company
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function task(): HasOne
    {
        return $this->hasOne(Task::class)->available();
    }

    /**
     * Get the user that owns the Company
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the verification associated with the Company
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function verification(): HasOne
    {
        return $this->hasOne(Verification::class)->withDefault(function ($verification) {
            $verification->status = 'unverified';
        });
    }

    /**
     * Get all of the transactions for the Company
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function transactions(): MorphMany
    {
        return $this->morphMany(Transaction::class, 'transactable')->flexible();
    }

    /**
     * Get all of the transactions for the Company
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function transacts(): MorphMany
    {
        return $this->morphMany(Transaction::class, 'transactable')->restricted();
    }

    /**
     * Return only the verified companies.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  bool  $verified
     */
    public function scopeVerified($query, $verified = true)
    {
        if ($verified === true) {
            return $query->whereHas('verification', function ($query) {
                $query->where('status', 'verified');
            })->orWhere('status', 'verified');
        } else {
            return $query->whereDoesntHave('verification', function ($query) {
                $query->where('status', 'verified');
            })->where('status', '!=', 'verified');
        }
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