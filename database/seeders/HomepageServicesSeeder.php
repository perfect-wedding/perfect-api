<?php

namespace Database\Seeders;

use App\Models\v1\Home\HomepageService;
use Illuminate\Database\Seeder;

class HomepageServicesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        HomepageService::truncate();
        HomepageService::insert([
            [
                'slug' => str('The market place')->slug(),
                'title' => 'The market place',
                'icon' => 'fa-solid fa-cart-shopping',
                'image' => 'http://localhost:8080/images/about.jpg',
                'image2' => 'http://localhost:8080/images/about.jpg',
                'content' => 'Designed to bring users and service providers in a single space, the market place is the go to place to find verified wedding marketplace service providers who are ranked by price and customer rating.',
            ],
            [
                'slug' => str('The Warehouse')->slug(),
                'title' => 'The Warehouse',
                'icon' => 'fa-solid fa-warehouse',
                'image' => 'http://localhost:8080/images/about.jpg',
                'image2' => 'http://localhost:8080/images/about.jpg',
                'content' => 'A space dedicated to rentals. From decorations, to cutleries, utensils, chairs, flowers and more, if you need it, then you can find it here. The warehouse is designed to service both users and service providers with an organized inventory system and a logistics pipeline for seamless operations.',
            ],
            [
                'slug' => str('The Vision board')->slug(),
                'title' => 'The Vision board',
                'icon' => 'fa-solid fa-palette',
                'image' => 'http://localhost:8080/images/about.jpg',
                'image2' => 'http://localhost:8080/images/about.jpg',
                'content' => 'Plan the perfect event in living color, our vision board gives you a visual representation of what your event would look like once it has been fully executed.',
            ],
            [
                'slug' => str('The Album')->slug(),
                'title' => 'The Album',
                'icon' => 'fa-solid fa-images',
                'image' => 'http://localhost:8080/images/about.jpg',
                'image2' => 'http://localhost:8080/images/about.jpg',
                'content' => 'Send pictures to anyone, anywhere, and at any time. The album feature is a view-only photo album that can only be accessed through a unique link generated for you, downloading and the ability to screenshot are disabled to ensure privacy and security.',
            ],
            [
                'slug' => str('Enterprise Resource Planning')->slug(),
                'title' => 'Enterprise Resource Planning',
                'icon' => 'fa-solid fa-building',
                'image' => 'http://localhost:8080/images/about.jpg',
                'image2' => 'http://localhost:8080/images/about.jpg',
                'content' => 'We provide an automated accounting, inventory system that handles the business end for you, so you can focus on creating master pieces.',
            ],
        ]);
    }
}
