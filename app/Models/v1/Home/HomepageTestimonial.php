<?php

namespace App\Models\v1\Home;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use ToneflixCode\LaravelFileable\Traits\Fileable;

class HomepageTestimonial extends Model
{
    use HasFactory, Fileable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'author',
        'title',
        'content',
        'image',
        'template',
    ];

    public function registerFileable(): void
    {
        $this->fileableLoader([
            'image' => 'default',
        ]);
    }

    public static function registerEvents()
    {
    }

    /**
     * Scope this content to it's parent
     *
     * @param [type] $query
     * @param [type] $parent_id
     * @return void
     */
    public function scopeParent($query, $parent_id)
    {
        $query->where('parent', $parent_id);
    }
}