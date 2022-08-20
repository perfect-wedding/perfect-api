<?php

namespace App\Services\Intervention\Fit;

use Intervention\Image\Filters\FilterInterface;
use Intervention\Image\Image;

class Media431 implements FilterInterface
{
    public function applyFilter(Image $image)
    {
        return $image->fit(431, 767);
    }
}