<?php

namespace App\Models\v1\Home;

use App\Traits\Imageable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HomepageOffering extends Model
{
    use HasFactory, Imageable;

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

    public function registerImageable()
    {
        $this->imageableLoader([
            'image' => 'default',
            'image2' => 'default'
        ]);
    }

    public static function registerEvents()
    {
    }
}
