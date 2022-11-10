<?php

namespace App\Models\v1;

use App\Notifications\SendCode;
use App\Traits\Extendable;
use App\Traits\Permissions;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
// use Musonza\Chat\Traits\Messageable;
use Lexx\ChatMessenger\Traits\Messagable;
use Propaganistas\LaravelPhone\Exceptions\CountryCodeException;
use Propaganistas\LaravelPhone\Exceptions\NumberFormatException;
use Propaganistas\LaravelPhone\Exceptions\NumberParseException;
use Propaganistas\LaravelPhone\PhoneNumber;
use ToneflixCode\LaravelFileable\Traits\Fileable;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable, Extendable, Permissions, Messagable, Fileable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        // 'privileges',
        'firstname',
        'lastname',
        'address',
        'country',
        'state',
        'city',
        'email',
        'phone',
        'username',
        'password',
        'type',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'email_verify_code',
        'phone_verify_code',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'email_verified_at' => 'datetime',
        'phone_verified_at' => 'datetime',
        'last_attempt' => 'datetime',
        'access_data' => 'array',
        'privileges' => 'array',
        'verified' => 'boolean',
        'settings' => 'array',
        'identity' => 'array',
        'dob' => 'datetime',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'about',
        'avatar',
        'fullname',
        'role_name',
        'wallet_bal',
        'role_route',
        'permissions',
        'basic_stats',
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array
     */
    protected $attributes = [
        'privileges' => '[]',
        'settings' => '{"newsletter":false,"updates":false, "noifications": false}',
        'identity' => '{}',
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
            ->orWhere('username', $value)
            ->firstOrFail();
    }

    public function registerFileable()
    {
        $this->fileableLoader([
            'image' => 'avatar',
        ]);
    }

    public static function registerEvents()
    {
        static::creating(function ($user) {
            $eser = Str::of($user->email)->explode('@');
            $user->username = $user->username ?? $eser->first(fn ($k) => (User::where('username', $k)
                ->doesntExist()), $eser->first().rand(100, 999));
        });
    }

    /**
     * Add a cool default for empty user about.
     *
     * @return string
     */
    protected function about(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ?? config('default_user_about', 'Only business minded!'),
        );
    }

    /**
     * Get all of the albums for the User
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function albums(): HasMany
    {
        return $this->hasMany(Album::class);
    }

    /**
     * Get the URL to the fruit bay category's photo.
     *
     * @return string
     */
    protected function avatar(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->images['image'] ?? $this->default_image,
        );
    }

    public function basicStats(): Attribute
    {
        $friends = $this->friends;

        return new Attribute(
            get: fn () => [
                'clients' => $this->clients->count(),
                'mutual_clients' => $friends->count(),
                'subscription' => $this->subscription()->firstOrNew(),
            ],
        );
    }

    /**
     * Get all of the vision boards for the User
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function boards(): HasMany
    {
        return $this->hasMany(VisionBoard::class);
    }

    /**
     * Get all of the clients for the User
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function clients(): HasMany
    {
        return $this->hasMany(Client::class);
    }

    /**
     * Get the User's default company
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get all of the transactions for the User
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function company_transactions(): HasManyThrough
    {
        return $this->hasManyThrough(Transaction::class, Service::class)->flexible();
    }

    /**
     * Get all of the companies for the User
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function companies(): HasMany
    {
        return $this->hasMany(Company::class);
    }

    /**
     * Get all of the mutual clients for the User
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function friends(): HasMany
    {
        $following_ids = $this->following()->get();
        $followers_ids = $this->followers()->get();
        $ids = $following_ids->intersect($followers_ids)->pluck('id');

        return $this->hasMany(Client::class)->whereIn('client_id', $ids);
    }

    /**
     * The following that belong to the CLient
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function following(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'clients', 'user_id', 'client_id');
    }

    /**
     * The following that belong to the CLient
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function followers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'clients', 'client_id', 'user_id');
    }

    public function fullname(): Attribute
    {
        return new Attribute(
            get: fn () => collect([
                $this->firstname,
                $this->lastname,
            ])->filter()->implode(' '),
        );
    }

    /**
     * Get name to use. Should be overridden in model to reflect your project
     *
     * @return string $name
     */
    public function getNameAttribute()
    {
        if ($this->firstname && $this->lastname) {
            return $this->fullname;
        }

        if ($this->firstname) {
            return $this->firstname;
        }

        if ($this->username) {
            return $this->username;
        }

        // if none is found, just return the email
        return $this->email;
    }

    public function hasVerifiedPhone()
    {
        return $this->phone_verified_at !== null;
    }

    public function markEmailAsVerified()
    {
        $this->last_attempt = null;
        $this->email_verify_code = null;
        $this->email_verified_at = now();
        $this->save();

        if ($this->wasChanged('email_verified_at')) {
            return true;
        }

        return false;
    }

    public function markPhoneAsVerified()
    {
        $this->last_attempt = null;
        $this->phone_verify_code = null;
        $this->phone_verified_at = now();
        $this->save();

        if ($this->wasChanged('phone_verified_at')) {
            return true;
        }

        return false;
    }

    /**
     * Get all of the markets for the User
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function markets(): HasMany
    {
        return $this->hasMany(Market::class);
    }

    /**
     * Get all of the orders belonging to the User
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Get all of the service order requests by the User
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function orderRequests(): HasMany
    {
        return $this->hasMany(OrderRequest::class);
    }

    /**
     * Interact with the user's permissions.
     *
     * @return  \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function permissions(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->getPermissions($this),
        );
    }

    /**
     * Interact with the user's phone.
     *
     * @return  \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function phone(): Attribute
    {
        return Attribute::make(
            set: function ($value) {
                try {
                    return ['phone' => $value ? (string) PhoneNumber::make($value, $this->ipInfo('country'))->formatE164() : $value];
                } catch (NumberParseException | NumberFormatException | CountryCodeException $th) {
                    return ['phone' => $value];
                }
            }
        );
    }

    /**
     * Get all of the user's reviews.
     */
    public function reviews()
    {
        return $this->morphMany(Review::class, 'reviewable');
    }

    /**
     * Get all of the reviews made by this user.
     */
    public function reviewsBy()
    {
        return $this->hasMany(Review::class);
    }

    /**
     * Get the category's stats.
     *
     * @return string
     */
    protected function stats(): Attribute
    {
        return Attribute::make(
            get: fn () => [
                'orders' => $this->orders()->count(),
                'reviews' => $this->reviews()->count(),
                'reviews-by' => $this->reviewsBy()->count(),
                'rating' => $this->reviews()->count() > 0 ? round($this->reviews()->pluck('rating')->avg(), 1) : 0.0,
            ],
        );
    }

    public function role(): Attribute
    {
        return new Attribute(
            get: fn ($value) => $this->companies()->count() > 1 && $value !== 'admin' && $this->company ? $this->company->type : $value,
        );
    }

    /**
     * Interact with the user's role.
     *
     * @return  \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function roleName(): Attribute
    {
        return Attribute::make(
            get: fn () => ($this->role === 'user'
                ? 'User'
                : ($this->role === 'vendor'
                    ? 'Vendor'
                    : ($this->role === 'provider'
                        ? 'Service Provider'
                        : ($this->role === 'concierge'
                            ? 'Concierge'
                            : 'Admin'
                        )
                    )
                )
            ),
        );
    }

    /**
     * Interact with the user's role.
     *
     * @return  \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function roleRoute(): Attribute
    {
        return Attribute::make(
            get: fn () => ($this->role === 'user'
                ? 'user.welcome'
                : ($this->role === 'vendor'
                    ? (! $this->companies ? 'auth.company' : 'warehouse.dashboard')
                    : ($this->role === 'provider'
                        ? (! $this->companies ? 'auth.company' : 'provider.dashboard')
                        : ($this->role === 'concierge'
                            ? 'concierge.dashboard'
                            : 'admin.dashboard'
                        )
                    )
                )
            ),
        );
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
        return [$this->email => $this->firstname];
    }

    /**
     * Route notifications for the twillio channel.
     *
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return array|string
     */
    public function routeNotificationForTwilio()
    {
        return $this->phone;
    }

    public function sendEmailVerificationNotification()
    {
        $this->last_attempt = now();
        $this->email_verify_code = mt_rand(100000, 999999);
        $this->save();

        $this->notify(new SendCode($this->email_verify_code, 'verify'));
    }

    public function sendPhoneVerificationNotification()
    {
        $this->last_attempt = now();
        $this->phone_verify_code = mt_rand(100000, 999999);
        $this->save();

        $this->notify(new SendCode($this->phone_verify_code, 'verify-phone'));
    }

    /**
     * Get the social_auth associated with the User
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function social_auth(): HasOne
    {
        return $this->hasOne(UserSocialAuth::class);
    }

    /**
     * Get the subscription associated with the User
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function subscription(): HasOne
    {
        return $this->hasOne(Subscription::class)->where('status', 'active');
    }

    /**
     * Get all of the tasks for the User
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'concierge_id');
    }

    /**
     * Get all of the transactions for the User
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Add funds or spend funds from the user's wallet.
     *
     * @return \App\Models\Wallet
     */
    public function useWallet($source, $amount, $detail = null, $type = null, $status = 'complete', $ref = null)
    {
        $wallet = $this->wallet_transactions()->firstOrNew();
        return $wallet->transact($source, $amount, $detail, $type, $status, $ref);
    }

    /**
     * Get all of the wallet transactions for the User
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function wallet_transactions(): HasMany
    {
        return $this->hasMany(Wallet::class);
    }

    public function walletBal(): Attribute
    {
        $credit = $this->wallet_transactions()->credit()->statusIs('complete');
        $debit = $this->wallet_transactions()->debit()->statusIs('complete');

        return new Attribute(
            get: fn () => $credit->sum('amount') - $debit->sum('amount'),
        );
    }
}