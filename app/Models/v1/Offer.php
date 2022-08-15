<?php

namespace App\Models\v1;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Offer extends Model
{
    use HasFactory;

    /**
     * The model's default values for attributes.
     *
     * @var array
     */
    protected $attributes = [
        'type' => 'increase',
    ];

    /**
     * Get the parent offerable model (service or warehouse).
     */
    public function offerable()
    {
        return $this->morphTo();
    }
}
