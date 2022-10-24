<?php

namespace App\Models\v1;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use ToneflixCode\LaravelFileable\Traits\Fileable;

class Feedback extends Model
{
    use HasFactory, Fileable;

    public function registerFileable()
    {
        $this->fileableLoader('image', 'default', true);
    }

    public static function registerEvents()
    {
    }

    /**
     * Get the user that sent the Feedback
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all of the replies for the Feedback
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function replies(): HasMany
    {
        return $this->hasMany(Feedback::class, 'thread_id');
    }

    /**
     * Get the Feedback the owns the reply
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function reply_thread(): BelongsTo
    {
        return $this->belongsTo(Feedback::class, 'thread_id');
    }

    public function scopeThread($query, $thread = false)
    {
        if ($thread === false) {
            $query->whereIn('type', ['feedback', 'bug', 'suggestion', 'complaint', 'other']);
        } else {
            return $query->where('type', 'thread')->where('thread_id', $thread);
        }
    }
}
