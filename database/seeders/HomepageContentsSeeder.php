<?php

namespace Database\Seeders;

use App\Models\v1\Home\HomepageContent;
use Illuminate\Database\Seeder;

class HomepageContentsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        HomepageContent::truncate();
        HomepageContent::insert([
            [
                'homepage_id' => 1,
                'slug' => str('About Us')->slug(),
                'title' => 'About Us',
                'subtitle' => 'Who we are',
                'leading' => 'We are doing wonderful things',
                'image' => 'http://localhost:8080/images/about.jpg',
                'image2' => 'http://localhost:8080/images/about.jpg',
                'linked' => true,
                'parent' => null,
                'attached' => json_encode([]),
                'content' => 'PerfectWedding.io is a tech company with the goal to use technology to provide marketplace vendors/service providers a seamless way to connect to the massive customer needs in this industry. Our perfect vision of the future is that everyone shou10ld be able to plan the perfect event with just a few clicks. At perfectWedding.io our primary focus is to curate and develop the commercial rules that govern our marketplace. Perfectwedding.io is a one-stop, go-to hub for wedding service providers. We connect you with the perfect venue, food caterers, photographers, and more in a time-efficient manner all in one place. Whether you\'re a wedding industry professional trying to find new clients, or someone planning an upcoming wedding, we\'ve got you covered.',
                'iterable' => false,
            ],
            [
                'homepage_id' => 1,
                'slug' => str('Our Services')->slug(),
                'title' => 'Services',
                'subtitle' => 'Our Services',
                'leading' => 'We are doing wonderful things',
                'image' => null,
                'image2' => null,
                'linked' => true,
                'parent' => null,
                'attached' => json_encode(['HomepageService']),
                'content' => null,
                'iterable' => true,
            ],
            [
                'homepage_id' => 1,
                'slug' => str('Community')->slug(),
                'title' => 'Community',
                'subtitle' => 'Community',
                'leading' => 'We are doing wonderful things',
                'image' => 'http://localhost:8080/images/about.jpg',
                'image2' => 'http://localhost:8080/images/about.jpg',
                'linked' => true,
                'parent' => null,
                'attached' => json_encode([]),
                'content' => fake()->sentences(10, true),
                'iterable' => false,
            ],
            [
                'homepage_id' => 1,
                'slug' => str('')->slug(),
                'title' => 'Team',
                'subtitle' => 'Meet The Team',
                'leading' => 'We are doing wonderful things',
                'image' => null,
                'image2' => null,
                'linked' => true,
                'parent' => null,
                'attached' => json_encode(['HomepageTeam']),
                'content' => null,
                'iterable' => false,
            ],
            [
                'homepage_id' => 1,
                'slug' => str('offerings')->slug(),
                'title' => 'What we offer',
                'subtitle' => 'We have designed a business model completely unique to this industry.',
                'leading' => 'We are doing wonderful things',
                'image' => null,
                'image2' => null,
                'linked' => false,
                'parent' => str('About Us')->slug(),
                'attached' => json_encode(['HomepageOffering']),
                'content' => null,
                'iterable' => true,
            ],
            [
                'homepage_id' => 1,
                'slug' => str('testimonials')->slug(),
                'title' => 'Testimonials',
                'subtitle' => 'Hear what people are saying about us.',
                'leading' => 'We are doing wonderful things',
                'image' => null,
                'image2' => null,
                'linked' => false,
                'parent' => str('About Us')->slug(),
                'attached' => json_encode(['HomepageTestimonial']),
                'content' => null,
                'iterable' => false,
            ],
        ]);
    }
}
