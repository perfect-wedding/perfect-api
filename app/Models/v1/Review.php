<?php

namespace App\Models\v1;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    use HasFactory;

    /**
     * The attributes that should be cast.
     *
     * @var array>string
     */
    protected $casts = [
        'rating' => 'float',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'rating',
        'comment',
        'user_id',
    ];

    /**
     * Get the parent reviewable model (service or inventory).
     */
    public function reviewable()
    {
        return $this->morphTo();
    }

    /**
     * Get the user that made the Review
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
