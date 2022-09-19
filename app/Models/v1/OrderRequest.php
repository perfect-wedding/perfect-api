<?php

namespace App\Models\v1;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderRequest extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'company_id',
        'package_id',
        'amount',
        'reason',
        'rejected',
        'accepted',
        'due_date',
        'destination',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'accepted' => 'boolean',
        'rejected' => 'boolean',
    ];

    /**
     * Get the user that made the Order Request
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the company that owns the Order Request
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the package associated with the Order Request
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function package(): BelongsTo
    {
        return $this->belongsTo(Offer::class);
    }

    /**
     * Get the parent orderable model (service or inventory).
     */
    public function orderable()
    {
        return $this->morphTo();
    }

    public function scopeAccepted($query)
    {
        return $query->where('accepted', true)->where('rejected', false);
    }

    public function scopeRejected($query)
    {
        return $query->where('rejected', true)->where('accepted', false);
    }

    public function scopePending($query)
    {
        return $query->where('rejected', false)->where('accepted', false);
    }

    public function scopeAvailable($query)
    {
        return $query->where('accepted', true)->orWhere('rejected', false);
    }
}
