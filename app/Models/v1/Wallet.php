<?php

namespace App\Models\v1;

use App\Traits\Meta;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Wallet extends Model
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

    public function topup($source, $amount, $detail = null): self
    {
        $reference = config('settings.trx_prefix', 'TRX-').$this->generate_string(20, 3);

        return $this->create([
            'user_id' => $this->user_id,
            'reference' => $reference,
            'amount' => $amount,
            'source' => $source,
            'detail' => $detail,
            'type' => 'credit',
        ]);
    }

    public function scopeCredit($query)
    {
        return $query->where('type', 'credit');
    }

    public function scopeStatusIs($query = null, $status = 'completed', $is = true)
    {
        if (in_array($status, ['pending','approved','complete','failed'])) {
            if ($is) {
                return $query->where('status', $status);
            }

            return $query->where('status', '!=', $status);
        }
    }

    public function scopeDebit($query)
    {
        $query->where('type', 'debit');
        $query->orWhere(function($q) {
            $q->where('type', 'withdrawal');
            $q->statusIs('failed', false);
        });
    }
}