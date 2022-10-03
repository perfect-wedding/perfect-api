<?php

namespace App\Models\v1;

use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StatusChangeRequests extends Model
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
        'current_status',
        'new_status',
        'user_id',
        'data',
    ];

    /**
     * Get the parent status_changeable model (service or inventory).
     */
    public function status_changeable()
    {
        return $this->morphTo();
    }
}
