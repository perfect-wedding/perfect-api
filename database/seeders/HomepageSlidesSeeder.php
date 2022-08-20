<?php

namespace Database\Seeders;

use App\Models\v1\Home\HomepageSlide;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class HomepageSlidesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        HomepageSlide::truncate();
        HomepageSlide::insert([
            [
                'homepage_id' => 1,
                'title' => 'A T   F I R S T   S I G H T',
                'subtitle' => 'so we meet...',
                'slug' => str('so we meet')->slug(),
                'color' => '--tf-perfect-yellow',
                'image' => 'http://localhost:8080/images/home-slides/1.jpg',
            ],
            [
                'homepage_id' => 1,
                'title' => 'C O U R T S H I P',
                'subtitle' => 'I have found the one...',
                'slug' => str('I have found the one')->slug(),
                'color' => '--tf-perfect-pink',
                'image' => 'http://localhost:8080/images/home-slides/2.jpg',
            ],
            [
                'homepage_id' => 1,
                'title' => 'T H E   W E D D I N G',
                'subtitle' => 'together we begin...',
                'slug' => str('together we begin')->slug(),
                'color' => '--tf-perfect-orange',
                'image' => 'http://localhost:8080/images/home-slides/3.jpg',
            ],
            [
                'homepage_id' => 1,
                'title' => 'C R E A T I N G    M E M O R I E S',
                'subtitle' => 'forever we stay...',
                'slug' => str('forever we stay')->slug(),
                'color' => '--tf-perfect-red',
                'image' => 'http://localhost:8080/images/home-slides/4.jpg',
            ],
        ]);
    }
}