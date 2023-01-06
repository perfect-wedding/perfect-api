<?php

namespace App\Models\v1;

use Illuminate\Database\Eloquent\Casts\Attribute;
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
        'recieved',
        'due_date',
        'location',
        'destination',
        'orderable_id',
        'tracking_data',
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
        'recieved' => 'boolean',
        'location' => 'collection',
        'tracking_data' => 'collection',
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
     * Attribute to determine if the user is disputing the order
     */
    public function disputing(): Attribute
    {
        return new Attribute(
            get: fn () => $this->changeRequest()->whereUserId(auth()->id())->disputed()->exists()
        );
    }

    /**
     * Attribute to determine if the order is disputed
     */
    public function disputed(): Attribute
    {
        return new Attribute(
            get: fn () => $this->changeRequest()->where('user_id', '!=', auth()->id())->disputed()->exists()
        );
    }

    /**
     * Get the calendar events for the company.
     */
    public function events(): MorphMany
    {
        return $this->morphMany(Event::class, 'eventable');
    }

    /**
     * Get all of the order's status change request.
     */
    public function changeRequest()
    {
        return $this->morphOne(ChangeRequest::class, 'status_changeable');
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