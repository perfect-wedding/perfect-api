<?php

namespace App\Services\Intervention\Fit;

use Intervention\Image\Filters\FilterInterface;
use Intervention\Image\Image;

class Media1080 implements FilterInterface
{
    public function applyFilter(Image $image)
    {
        return $image->fit(1080, 767);
    }
}
