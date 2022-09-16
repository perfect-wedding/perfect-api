<?php

namespace App\Models\v1;

use App\Traits\Meta;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'balance',
    ];

    public function balance(): Attribute
    {
        return new Attribute(
            get: fn () => $this->where('type', 'credit')->sum('amount'),
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
}
