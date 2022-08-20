<?php

namespace App\Models\v1\Home;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Homepage extends Model
{
    use HasFactory;

    /**
     * Get all of the slides for the Homepage
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function slides(): HasMany
    {
        return $this->hasMany(HomepageSlide::class, 'homepage_id');
    }

    /**
     * Get all of the content for the Homepage
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function content(): HasMany
    {
        return $this->hasMany(HomepageContent::class, 'homepage_id');
    }
}
