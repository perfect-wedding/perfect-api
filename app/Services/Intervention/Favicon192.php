<?php

namespace App\Services\Intervention;

use Intervention\Image\Filters\FilterInterface;
use Intervention\Image\Image;

class Favicon192 implements FilterInterface
{
    public function applyFilter(Image $image)
    {
        return $image->fit(192, 192);
    }
}
