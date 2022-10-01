<?php

namespace App\Models\v1\Home;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use ToneflixCode\LaravelFileable\Traits\Fileable;

class HomepageService extends Model
{
    use HasFactory, Fileable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'title',
        'content',
        'icon',
        'template',
    ];

    public function registerFileable(): void
    {
        $this->fileableLoader([
            'image' => 'default',
            'image2' => 'default',
        ]);
    }

    public static function registerEvents()
    {
        static::creating(function ($item) {
            $slug = str($item->title)->slug();
            $item->slug = (string) Homepage::whereSlug($slug)->exists() ? $slug->append(rand()) : $slug;
        });
    }
}
