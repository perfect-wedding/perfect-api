<?php

namespace App\Models\v1;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use ToneflixCode\LaravelFileable\Traits\Fileable;

class Verification extends Model
{
    use HasFactory, Fileable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'concierge_id',
        'company_id',
        'status',
        'exists',
        'observations',
        'real_address',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'exists' => 'boolean',
        'real_address' => 'boolean',
        'rejected_docs' => 'array'
    ];

    /**
     * The model's attributes.
     *
     * @var array<string, string>
     */
    protected $attributes = [
        'rejected_docs' => '[]'
    ];

    public function registerFileable()
    {
        $this->fileableLoader([
            'doc_ownerid' => 'private.docs',
            'doc_owner' => 'private.docs',
            'doc_inventory' => 'private.docs',
            'doc_invoice' => 'private.docs',
            'doc_cac' => 'private.docs',
        ]);
    }

    public static function registerEvents()
    {
    }

    /**
     * Get the user that owns the Verification
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the company that owns the Verification
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the concierge that owns the Verification
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function concierge(): BelongsTo
    {
        return $this->belongsTo(User::class, 'concierge_id');
    }
}
