<?php

namespace App\Models\v1;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Meta;

class Call extends Model
{
    use HasFactory, Meta;

    protected $fillable = [
        'caller_id',
        'participant_ids',
        'missed_participant_ids',
        'rejected_participant_ids',
        'accepted_participant_ids',
        'meta',
        'type',
        'subject',
        'room_name',
        'room_pass',
        'ongoing',
        'origin',
        'started_at',
        'ended_at',
    ];

    protected $casts = [
        'participant_ids' => 'collection',
        'missed_participant_ids' => 'collection',
        'rejected_participant_ids' => 'collection',
        'accepted_participant_ids' => 'collection',
        'meta' => 'collection',
        'ongoing' => 'boolean',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    protected $attributes = [
        'participant_ids' => '[]',
        'missed_participant_ids' => '[]',
        'rejected_participant_ids' => '[]',
        'accepted_participant_ids' => '[]',
        'meta' => '[]',
    ];

    // Add password and room_name when creating a new call
    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $charset = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
            $model->room_pass = rand(100000, 999999);
            $model->room_name = str(auth()->user()->username . '-')
                ->append((new self)->generate_string(rand(5, 6), null, $charset) . '-')
                ->append($model->type . '-')
                ->append((new self)->generate_string(rand(5, 6), null, $charset))
                ->lower()->replace('-', '-')->toString();
        });
    }


    public function acceptedParticipants(): Attribute
    {
        return new Attribute(
            get: fn () => User::whereIn('id', json_decode($this->accepted_participant_ids))->get()->map(fn ($user) => $this->buildUser($user))
        );
    }

    public function buildUser(User $user)
    {
        return $user->only('id', 'fullname', 'email', 'avatar', 'username', 'onlinestatus');
    }

    public function caller(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'caller_id');
    }

    public function event(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'event_id');
    }

    public function missedParticipants(): Attribute
    {
        return new Attribute(
            get: fn () => User::whereIn('id', json_decode($this->missed_participant_ids))->get()->map(fn ($user) => $this->buildUser($user))
        );
    }

    public function ongoing(): Attribute
    {
        return new Attribute(
            get: fn () => $this->ended_at === null && $this->accepted_participant_ids->isNotEmpty(),
        );
    }

    public function participants(): Attribute
    {
        return new Attribute(
            get: fn () => User::whereIn('id', json_decode($this->participant_ids))->get()->map(fn ($user) => $this->buildUser($user)),
        );
    }

    public function rejectedParticipants(): Attribute
    {
        return new Attribute(
            get: fn () => User::whereIn('id', json_decode($this->rejected_participant_ids))->get()->map(fn ($user) => $this->buildUser($user))
        );
    }

    public function scopeIsOngoing($query, $value = true)
    {
        $query->where('accepted_participant_ids', '!=', '[]');
        $query->where(function ($query) use ($value) {
            if ($value) {
                $query->whereNotNull('started_at');
                $query->whereNull('ended_at');
            } else {
                $query->whereNull('started_at');
                $query->orWhereNotNull('ended_at');
            }
        });
    }

    public function scopeIsMissed($query, $user_id, $value = true)
    {
        if ($value) {
            return $query->whereJsonContains('missed_participant_ids', $user_id);
        } else {
            return $query->whereJsonDoesntContain('missed_participant_ids', $user_id);
        }
    }

    public function scopeIsAccepted($query, $user_id, $value = true)
    {
        if ($value) {
            return $query->whereJsonContains('accepted_participant_ids', $user_id);
        } else {
            return $query->whereJsonDoesntContain('accepted_participant_ids', $user_id);
        }
    }

    public function scopeIsCaller($query, $user_id, $value = true)
    {
        if ($value) {
            return $query->where('caller_id', $user_id);
        } else {
            return $query->where('caller_id', '!=', $user_id);
        }
    }

    public function scopeIsEnded($query, $value = true)
    {
        $query->where(function ($query) use ($value) {
            if ($value) {
                $query->whereNotNull('ended_at');
            } else {
                $query->whereNull('ended_at');
            }
        });
    }

    public function scopeIsParticipant($query, $user_id, $value = true)
    {
        if ($value) {
            return $query->whereJsonContains('participant_ids', $user_id);
        } else {
            return $query->whereJsonDoesntContain('participant_ids', $user_id);
        }
    }

    public function scopeIsRejected($query, $user_id, $value = true)
    {
        if ($value) {
            return $query->whereJsonContains('rejected_participant_ids', $user_id);
        } else {
            return $query->whereJsonDoesntContain('rejected_participant_ids', $user_id);
        }
    }

    public function scopeNoAnswer($query, $value = true)
    {
        if ($value) {
            $query->whereNotNull('started_at');
            $query->whereNull('ended_at');
            $query->whereNull('accepted_participant_ids');
        } else {
            $query->whereNull('started_at');
            $query->orWhereNotNull('ended_at');
            $query->orWhereNotNull('accepted_participant_ids');
        }
    }
}