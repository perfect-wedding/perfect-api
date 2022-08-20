<?php

namespace App\Models\v1\Home;

use App\Traits\Imageable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HomepageTeam extends Model
{
    use HasFactory, Imageable;

    public function registerImageable()
    {
        $this->imageableLoader([
            'image' => 'default',
        ]);
    }

    public static function registerEvents()
    {
    }


    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'socials' => 'array',
    ];
}