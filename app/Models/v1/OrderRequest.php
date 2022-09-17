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
        'amount',
        'reason',
        'rejected',
        'accepted',
        'due_date',
        'destination',
        'orderable_id',
        'orderable_type',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'accepted' => 'datetime',
    ];

    /**
     * Get the user that made the Order
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the company that owns the Order
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
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
        return $query->where('accepted', '!=', NULL)->where('rejected', NULL);
    }

    public function scopeRejected($query)
    {
        return $query->where('rejected', '!=', NULL)->where('accepted', NULL);
    }
}
