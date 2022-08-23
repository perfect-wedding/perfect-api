<?php

namespace App\Models\v1;

use App\Notifications\SendCode;
use App\Services\Media;
use App\Traits\Permissions;
use App\Traits\Extendable;
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
use Propaganistas\LaravelPhone\Exceptions\CountryCodeException;
use Propaganistas\LaravelPhone\Exceptions\NumberFormatException;
use Propaganistas\LaravelPhone\Exceptions\NumberParseException;
use Propaganistas\LaravelPhone\PhoneNumber;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable, Extendable, Permissions;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'privileges',
        'firstname',
        'lastname',
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
        'privileges' => 'array',
        'access_data' => 'array',
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
    ];

    protected static function booted()
    {
        static::creating(function ($user) {
            $eser = Str::of($user->email)->explode('@');
            $user->username = $user->username ?? $eser->first(fn ($k) =>(User::where('username', $k)
                ->doesntExist()), $eser->first().rand(100, 999));
        });

        static::saving(function ($user) {
            $user->image = (new Media)->save('avatar', 'image', $user->image);
        });

        static::deleted(function ($user) {
            (new Media)->delete('avatar', $user->image);
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
            get: fn () => (new Media)->image('avatar', $this->image),
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
     * Get all of the companies for the User
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function companies(): HasMany
    {
        return $this->hasMany(Company::class);
    }

    /**
     * Get the company that owns the User
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
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
     * Get all of the clients for the User
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function clients(): HasMany
    {
        return $this->hasMany(Client::class);
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
                ? 'market'
                : ($this->role === 'vendor'
                     ? 'warehouse.home'
                     : ($this->role === 'provider'
                         ? 'provider.home'
                         : ($this->role === 'concierge'
                             ? 'concierge.home'
                             : 'admin.dashboard'
                         )
                     )
                 )
             ),
        );
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
     * Get all of the transactions for the User
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function company_transactions(): HasManyThrough
    {
        return $this->hasManyThrough(Transaction::class, Service::class);
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
     * Get the subscription associated with the User
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function subscription(): HasOne
    {
        return $this->hasOne(Subscription::class)->where('status', 'active');
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
        $credit = Wallet::where([['type', 'credit'], ['user_id', auth()->id()]]);
        $debit = Wallet::where([['type', 'debit'], ['user_id', auth()->id()]]);

        return new Attribute(
            get: fn () => $credit->sum('amount') - $debit->sum('amount'),
        );
    }
}
