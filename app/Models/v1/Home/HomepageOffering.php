<?php

namespace App\Models\v1\Home;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use ToneflixCode\LaravelFileable\Traits\Fileable;

class HomepageOffering extends Model
{
    use HasFactory, Fileable;

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'features' => 'array',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'title',
        'subtitle',
        'icon',
        'features',
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
    }
}