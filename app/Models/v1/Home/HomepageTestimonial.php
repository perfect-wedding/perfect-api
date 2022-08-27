<?php

namespace App\Models\v1\Home;

use App\Traits\Imageable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HomepageTestimonial extends Model
{
    use HasFactory, Imageable;

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

    public function registerImageable()
    {
        $this->imageableLoader([
            'image' => 'default',
        ]);
    }

    public static function registerEvents()
    {
    }
}
