<?php

namespace App\Models\v1;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends Model
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
        'code',
        'amount',
        'status',
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
        'due_date' => 'datetime',
        'accepted' => 'boolean',
    ];

    /**
     * Get the parent orderable model (service or inventory).
     */
    public function orderable()
    {
        return $this->morphTo();
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
     * Get the user that made the Order
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeAccepted($query)
    {
        return $query->where('accepted', true)->where('status', '!=', 'rejected');
    }

    public function scopeRejected($query)
    {
        return $query->where('accepted', '!=', true)->where('status', 'rejected');
    }
}