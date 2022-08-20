<?php

namespace App\Services\Intervention;

use Intervention\Image\Filters\FilterInterface;
use Intervention\Image\Image;

class Favicon180 implements FilterInterface
{
    public function applyFilter(Image $image)
    {
        return $image->fit(180, 180);
    }
}
