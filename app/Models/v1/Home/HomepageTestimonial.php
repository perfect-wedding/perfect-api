<?php

namespace App\Models\v1\Home;

use App\Traits\Imageable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HomepageTestimonial extends Model
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
}