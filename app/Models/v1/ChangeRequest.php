<?php

namespace App\Models\v1;

use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChangeRequest extends Model
{
    use HasFactory;

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'data' => AsCollection::class,
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'status_changeable_type',
        'status_changeable_id',
        'current_status',
        'new_status',
        'user_id',
        'reason',
        'data',
    ];

    /**
     * Get the user who rejected ChangeRequest
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function rejector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejector_id')->withDefault(function () {
            return collect((object) []);
        });
    }

    /**
     * Get the parent status_changeable model (service or inventory).
     */
    public function status_changeable()
    {
        return $this->morphTo();
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeAccepted($query)
    {
        return $query->where('status', 'accepted');
    }

    public function scopeDisputed($query)
    {
        return $query->where('status', 'disputed')->where('reason', '!=', null);
    }

    public function scopeRecieved($query)
    {
        return $query->where('user_id', '!=', auth()->id());
    }

    public function scopeSent($query)
    {
        return $query->where('user_id', auth()->id());
    }
}
