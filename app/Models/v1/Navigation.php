<?php

namespace App\Models\v1;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Navigation extends Model
{
    use HasFactory;

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'active' => 'boolean',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'title',
        'location',
        'group',
        'active',
        'priority',
    ];

    /**
     * Get the parent navigable model (Homepage, Company, Service, Inventory, ShopItem, Category).
     */
    public function navigable()
    {
        return $this->morphTo();
    }

    public function title(): Attribute
    {
        return new Attribute(
            get: fn ($value) => $value ? $value : ($this->navigable->title ?? $this->navigable->name ?? ''),
        );
    }

    public function slug(): Attribute
    {
        return new Attribute(
            get: fn ($value) => $this->navigable->slug ?? $this->navigable->id ?? $value ?? '',
        );
    }

    /**
     * Scope a query to only include navigations by the group.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param string $group
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByGroup($query, $group)
    {
        return $query->where('group', $group);
    }

    /**
     * Scope a query to only include navigations by the location.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param string $location
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByLocation($query, $location)
    {
        return $query->where('location', $location);
    }

    /**
     * Scope a query to only include active navigations.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query) {
        return $query->where('active', true);
    }

    /**
     * Scope a query to only include inactive navigations.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInactive($query) {
        return $query->where('active', false);
    }
}
