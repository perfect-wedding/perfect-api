<?php

namespace App\Models\v1;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Transaction extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'data',
        'reference',
        'status',
        'method',
        'amount',
        'due',
        'transactable_id',
        'transactable_type',
        'offer_charge',
        'discount',
        'restricted',
    ];

    /**
     * The attributes to be appended
     *
     * @var array
     */
    protected $appends = [
        'type',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'data' => 'array',
        'restricted' => 'boolean',
    ];

    /**
     * Get the transaction's transactable model (probably a fruit bay item).
     */
    public function transactable()
    {
        return $this->morphTo();
    }

    public function type(): Attribute
    {
        return new Attribute(
            get: fn () => Str::replace('App\\Models\\', '', $this->transactable_type),
        );
    }

    /**
     * Get the user that owns the Transaction
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the company that owns the Transaction
     */
    public function company(): Attribute
    {
        return new Attribute(
            get: fn () => DB::table('companies')
                ->join('services', 'services.company_id', 'companies.id')
                ->join('transactions', 'transactions.transactable_id', 'services.id')
                ->where('transactions.id', $this->id)->get('companies.*')->first()
        );
    }

    /**
     * Get the company that owns the Transaction
     */
    public function invoice(): Attribute
    {
        return new Attribute(
            get: fn () => Transaction::where('reference', $this->reference)->get()->pluck('transactable')
        );
    }

    public function scopeRestricted($query)
    {
        $query->where('restricted', true);
    }

    public function scopeFlexible($query)
    {
        $query->where('restricted', false);
    }
}
