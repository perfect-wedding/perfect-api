<?php

namespace App\Models\v1;

use App\Services\Media;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'phone',
        'email',
        'type',
        'role',
        'intro',
        'about',
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
     * Get all of the inventory for the Company
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function inventory(): HasMany
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
     * Get all of the services for the Company
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    /**
     * Get all of the orders for the Company
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Get all of the reviews for the company.
     */
    public function reviews()
    {
        return Review::whereHasMorph('reviewable', Service::class, function ($q) {
            $q->where('company_id', $this->id);
        });
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
            get: fn () => $this->task ? $this->task->status : $this->verification->status,
        );
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
     * Get all of the transactions for the User
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function transactions(): MorphMany
    {
        return $this->morphMany(Transaction::class, 'transactable')->flexible();
    }

    /**
     * Get all of the transactions for the User
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function transacts(): MorphMany
    {
        return $this->morphMany(Transaction::class, 'transactable')->restricted();
    }
}
