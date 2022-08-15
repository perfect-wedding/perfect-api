<?php

namespace App\Models\v1;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Stat extends Model
{
    use HasFactory;

    protected $fillable = [
        'metric',
        'user_data',
        'user_id',
    ];

    protected $casts = [
        'user_data' => 'array',
    ];

    public function statable()
    {
        return $this->morphTo();
    }

    public function type(): Attribute
    {
        return new Attribute(
            get: fn ($value, $attr) => Str::replace('App\Models\v1', '', $attr['statable_type']),
        );
    }
}
