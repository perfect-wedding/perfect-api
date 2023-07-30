<?php

use App\Services\Intervention\Fit\Media1080;
use App\Services\Intervention\Fit\Media431;
use App\Services\Intervention\Fit\Media694;
use App\Services\Intervention\Fit\Media720;

return [

    /*
    |--------------------------------------------------------------------------
    | Name of route
    |--------------------------------------------------------------------------
    |
    | Enter the routes name to enable dynamic imagecache manipulation.
    | This handle will define the first part of the URI:
    |
    | {route}/{template}/{filename}
    |
    | Examples: "images", "img/cache"
    |
    */

    'route' => 'images/responsive',

    /*
    |--------------------------------------------------------------------------
    | Storage paths
    |--------------------------------------------------------------------------
    |
    | The following paths will be searched for the image filename, submitted
    | by URI.
    |
    | Define as many directories as you like.
    |
    */

    'paths' => [
        public_path('web/assets/img'),
        public_path('images'),
        public_path('images/home-slides'),
        public_path('images/bank'),
        public_path('images/bank/collect'),
        public_path('images/categories'),
        public_path('images/testimonials'),

        public_path('avatars'),
        public_path('media'),
        public_path('media/banners'),
        public_path('media/images'),
        public_path('media/logos'),

        storage_path('app/public/avatars'),
        storage_path('app/public/media'),
        storage_path('app/public/media/banners'),
        storage_path('app/public/media/images'),
        storage_path('app/public/media/logos'),
        storage_path('app/files/images'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Manipulation templates
    |--------------------------------------------------------------------------
    |
    | Here you may specify your own manipulation filter templates.
    | The keys of this array will define which templates
    | are available in the URI:
    |
    | {route}/{template}/{filename}
    |
    | The values of this array will define which filter class
    | will be applied, by its fully qualified name.
    |
    */

    'templates' => [
        'small' => 'Intervention\Image\Templates\Small',
        'medium' => 'Intervention\Image\Templates\Medium',
        'large' => 'Intervention\Image\Templates\Large',
        '431' => Media431::class,
        '694' => Media694::class,
        '720' => Media720::class,
        '1080' => Media1080::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Image Cache Lifetime
    |--------------------------------------------------------------------------
    |
    | Lifetime in minutes of the images handled by the imagecache route.
    |
    */

    'lifetime' => 43200,

];
