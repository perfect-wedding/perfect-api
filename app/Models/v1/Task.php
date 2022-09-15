<?php

namespace App\Models\v1;

use App\Traits\CalculateTimeDiff;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Task extends Model
{
    use HasFactory, CalculateTimeDiff;

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'ends_at' => 'datetime',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'concierge_id',
        'company_id',
        'ends_at',
    ];

    /**
     * Get the company that owns the Task
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the concierge that owns the Task
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function concierge(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the verification associated with the Company
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function verifications(): HasManyThrough
    {
        return $this->hasManyThrough(Verification::class, Company::class, 'id', 'company_id', 'company_id', 'id');
    }

    public function timeLeft(): Attribute
    {
        return new Attribute(
            get: fn () => $this->until('ends_at', true),
        );
    }

    public function timerActive(): Attribute
    {
        return new Attribute(
            get: fn () => $this->timer_active('ends_at', true),
        );
    }

    /**
     * Scope a query to only include available tasks.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeLocked($query, $has = true)
    {
        $query->where(function($query) {
            $query->where('ends_at', '>', now());
            $query->whereStatus('pending');
        })->orWhere(function($query) {
            $query->where('status', 'complete');
            $query->orWhere('status', 'approved');
        })->orWhereHas('verifications', function($q) {
            $q->where('verifications.status', 'rejected');
            $q->where('verifications.concierge_id', auth()->id());
        });
    }

    /**
     * Scope a query to only include available tasks.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeAvailable($query, $has = true)
    {
        $query->where('status', '!=', 'released');
        $query->where(function($query) use ($has) {
            $query->where(function($query) use ($has) {
                $operator = $has ? '>' : '<';
                $query->where('ends_at', $operator, now());
                $query->whereStatus('pending');
            })->orWhereHas('verifications', function($q) {
                $q->where('verifications.status', 'rejected');
                $q->where('verifications.concierge_id', auth()->id());
            });
        });
    }

    /**
     * Scope a query to only include completed tasks.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCompleted($query)
    {
        $query->where(function($query) {
            $query->where('status', 'complete');
            $query->orWhere('status', 'approved');
        })->whereDoesntHave('verifications', function($q) {
            $q->where('verifications.status', 'rejected');
            $q->where('verifications.concierge_id', auth()->id());
        });
    }
}