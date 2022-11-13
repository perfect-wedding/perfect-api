<?php

namespace Database\Seeders;

use App\Models\v1\Advert;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AdvertSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $adverts = [
            [
                'icon' => 'fa-solid fa-box',
                'title' => '{user} you\'re doing great!',
                'details' => 'Today is a good day to make money',
                'media' => 'http://localhost:8080/images/testimonials/ceo-blings-philip.jpeg',
                'url' => null,
                'meta' => [
                    'align' => 'start',
                    'justify' => 'end',
                    'welcome' => true,
                    'dummy' => true,
                ],
                'places' => [
                    'concierge.dashboard',
                ],
                'active' => true,
            ],
            [
                'icon' => 'fa-solid fa-box',
                'title' => '{user} you\'re doing great!',
                'details' => 'Today is a good day to make money',
                'media' => 'http://localhost:8080/images/testimonials/ceo-blings-philip.jpeg',
                'url' => null,
                'meta' => [
                    'align' => 'start',
                    'justify' => 'end',
                    'welcome' => true,
                    'dummy' => true,
                ],
                'places' => [
                    'vendor.dashboard',
                ],
                'active' => true,
            ],
            [
                'icon' => 'fa-solid fa-box',
                'title' => '{user} you\'re doing great!',
                'details' => 'Today is a good day to make money',
                'media' => 'http://localhost:8080/images/testimonials/ceo-blings-philip.jpeg',
                'url' => null,
                'meta' => [
                    'align' => 'start',
                    'justify' => 'end',
                    'welcome' => true,
                    'dummy' => true,
                ],
                'places' => [
                    'provider.dashboard',
                ],
                'active' => true,
            ],
        ];

        // Delete all adverts having meta->dummy = true
        Advert::where('meta->dummy', true)->delete();
        // Free auto-incrementing id
        if (Advert::max('id') > 0) {
            \DB::statement('ALTER TABLE adverts AUTO_INCREMENT = ?;', [Event::max('id') + 1]);
        } else {
            \DB::statement('ALTER TABLE adverts AUTO_INCREMENT = 1;');
        }


        // Seed the database
        foreach ($adverts as $advert) {
            Advert::create($advert);
        }
    }
}