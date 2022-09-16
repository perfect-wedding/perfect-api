<?php

namespace App\Models\v1;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSocialAuth extends Model
{
    use HasFactory;

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'github_expires_at' => 'datetime',
        'google_expires_at' => 'datetime',
        'facebook_expires_at' => 'datetime',
        'twitter_expires_at' => 'datetime',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'email',
        'github_id',
        'github_token',
        'github_refresh_token',
        'github_expires_at',
        'google_id',
        'google_token',
        'google_refresh_token',
        'google_expires_at',
        'facebook_id',
        'facebook_token',
        'facebook_refresh_token',
        'facebook_expires_at',
        'twitter_id',
        'twitter_token',
        'twitter_refresh_token',
        'twitter_expires_at',
    ];

    /**
     * Get the user that owns the UserSocialAuth
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
