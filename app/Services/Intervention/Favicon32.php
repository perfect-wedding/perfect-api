<?php

namespace App\Services\Intervention;

use Intervention\Image\Filters\FilterInterface;
use Intervention\Image\Image;

class Favicon32 implements FilterInterface
{
    public function applyFilter(Image $image)
    {
        return $image->fit(32, 32);
    }
}
