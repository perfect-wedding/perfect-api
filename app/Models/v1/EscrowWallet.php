<?php

namespace App\Models\v1;

use App\Traits\Meta;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class EscrowWallet extends Model
{
    use HasFactory, Meta;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'reference',
        'amount',
        'source',
        'detail',
        'type',
        'status',
        'walletable_id',
        'walletable_type',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'escaped' => 'boolean',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'balance',
    ];

    /**
     * Get the user that owns the Service
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function balance(): Attribute
    {
        return new Attribute(
            get: fn () => $this->credit()->sum('amount'),
        );
    }

    public function scopeCredit($query)
    {
        return $query->where('type', 'credit');
    }

    public function scopeStatusIs($query = null, $status = 'completed', $is = true)
    {
        if (in_array($status, ['pending', 'approved', 'complete', 'declined', 'failed'])) {
            if ($is) {
                return $query->where('status', $status);
            }

            return $query->where('status', '!=', $status);
        }
    }

    public function scopeDebit($query)
    {
        $query->where('type', 'debit');
        $query->orWhere(function ($q) {
            $q->where('type', 'withdrawal');
            $q->statusIs('failed', false);
        });
    }

    public function transact($source, $amount, $detail = null, $type = null, $status = 'complete', $ref = null): self
    {
        $reference = $ref ?? config('settings.trx_prefix', 'TRX-').$this->generate_string(20, 3);

        // Ensure type is either withdrawal, credit or debit
        if (! $type) {
            $type = $amount > 0 ? 'credit' : 'debit';
        } else {
            $type = in_array($type, ['credit', 'debit', 'withdrawal']) ? $type : 'debit';
        }

        return $this->create([
            'user_id' => $this->user_id,
            'reference' => $reference,
            'amount' => abs($amount),
            'source' => $source,
            'detail' => $detail,
            'type' => $type,
            'status' => $status,
            'walletable_id' => $this->walletable_id,
            'walletable_type' => $this->walletable_type,
        ]);
    }

    /**
     * Get all of the transactions for the Wallet
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function transactions(): MorphMany
    {
        return $this->morphMany(Transaction::class, 'transactable')->flexible();
    }

    /**
     * Get all of the walletable models.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function walletable(): \Illuminate\Database\Eloquent\Relations\MorphTo
    {
        return $this->morphTo();
    }
}
