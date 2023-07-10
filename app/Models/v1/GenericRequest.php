<?php

namespace App\Models\v1;

use App\Notifications\GenericRequest as NotificationsGenericRequest;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GenericRequest extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'sender_id',
        'meta',
        'model',
        'message',
        'rejected',
        'accepted',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'accepted' => 'boolean',
        'rejected' => 'boolean',
        'meta' => 'collection',
    ];

    /**
     * Get the user that made the Request
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * Get the user recieveing the Request
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the notification associated with the GenericRequest
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function notification(): Attribute
    {
        return Attribute::make(
            get: fn () => auth()->user()->notifications()
                ->whereType(NotificationsGenericRequest::class)
                ->where('data->request->id', $this->id)->first(),
        );
    }

    public function scopeAccepted($query)
    {
        $query->where('accepted', true)->where('rejected', false);
    }

    public function scopeRejected($query)
    {
        $query->where('rejected', true)->where('accepted', false);
    }

    public function scopePending($query)
    {
        $query->where('rejected', false)->where('accepted', false);
    }

    public function scopeOutgoing($query)
    {
        $query->where('sender_id', auth()->id());
    }

    public function scopeIncoming($query)
    {
        $query->where('user_id', auth()->id());
    }

    public function scopeOwn($query)
    {
        $query->where('user_id', auth()->id());
        $query->orWhere('sender_id', auth()->id());
    }

    public function status(): Attribute
    {
        return new Attribute(
            get: fn () => $this->accepted && ! $this->rejected
                ? 'accepted'
                : ($this->rejected && ! $this->accepted
                    ? 'rejected'
                    : 'pending'
                ),
        );
    }
}