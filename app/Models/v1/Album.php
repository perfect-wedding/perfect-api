<?php

namespace App\Models\v1;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use ToneflixCode\LaravelFileable\Traits\Fileable;

class Album extends Model
{
    use HasFactory, Fileable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'title',
        'info',
        'privacy',
        'disclaimer',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'meta' => 'array',
        'expires_at' => 'datetime',
        'restricted' => 'boolean',
    ];

    public function registerFileable()
    {
        $this->fileableLoader([
            'cover_f' => 'album',
            'cover_b' => 'album',
        ]);
    }

    public static function registerEvents()
    {
        static::creating(function ($item) {
            $slug = str($item->title)->slug();
            $item->slug = (string) Album::whereSlug($slug)->exists() ? $slug->append(rand()) : $slug;
        });

        static::deleting(function ($item) {
            $item->images()->delete();
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

    /**
     * Get all of the album's images.
     */
    public function images()
    {
        return $this->morphMany(Image::class, 'imageable');
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

    public function shareToken(): Attribute
    {
        // Return the share token if expired_at is not null and is not expired otherwise return null
        return Attribute::make(
            get: fn ($token) => $this->expires_at && !$this->expires_at->isPast() ? $token ?? base64url_encode($this->slug) : null,
        );
    }

    public function scopeByToken($query, $token)
    {
        return $query->where(function($query) use ($token) {
            $query->whereNotNull('share_token')
                 ->where('share_token', $token);
        })
        ->orWhere('slug', base64url_decode($token));
    }

    /**
     * Get all of the transactions for the Company
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function transactions(): MorphMany
    {
        return $this->morphMany(Transaction::class, 'transactable')->restricted();
    }
}
