<?php

namespace App\Models\v1\Home;

use App\Traits\Imageable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HomepageService extends Model
{
    use HasFactory, Imageable;

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