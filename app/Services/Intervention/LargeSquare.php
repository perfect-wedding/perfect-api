<?php

namespace App\Services\Intervention;

use Intervention\Image\Filters\FilterInterface;
use Intervention\Image\Image;

class LargeSquare implements FilterInterface
{
    public function applyFilter(Image $image)
    {
        return $image->fit(480, 480);
    }
}
