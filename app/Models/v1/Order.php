<?php

namespace App\Models\v1;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Order extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'company_type',
        'company_id',
        'user_id',
        'color',
        'code',
        'qty',
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
        return $this->morphTo('orderable');
    }

    /**
     * Get the parent company model (Company or GiftShop).
     */
    public function company()
    {
        return $this->morphTo('company');
    }

    /**
     * Get the calendar events for the company.
     *
     */
    public function events(): MorphMany
    {
        return $this->morphMany(Event::class, 'eventable');
    }

    /**
     * Get all of the order's status change request.
     */
    public function statusChangeRequest()
    {
        return $this->morphOne(StatusChangeRequests::class, 'status_changeable');
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
        return $query->where('status', 'accepted')->where('accepted', true);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in-progress');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeDelivered($query)
    {
        return $query->where('status', 'delivered');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeCancelled($query, $true = true)
    {
        return $query->where('status', ($true ? '=' : '!='), 'cancelled');
    }

    public function scopeByCompany($query, $id)
    {
        $query->whereCompanyId($id);
        $query->whereCompanyType(Company::class);
    }

    public function scopeByGiftShop($query, $id)
    {
        $query->whereCompanyId($id);
        $query->whereCompanyType(GiftShop::class);
    }
}
