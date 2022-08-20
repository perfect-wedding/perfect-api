<?php

namespace Database\Seeders;

use App\Models\v1\Home\HomepageTeam;
use App\Models\v1\Home\HomepageTestimonial;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class HomepageTestimonialsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        HomepageTestimonial::truncate();
        HomepageTestimonial::insert([
            [
              "image" => "http://localhost:8080/images/testimonials/event-planner-mary.jpeg",
              "title" => "Event Planner",
              "author" => "Mary",
              "content" => "This app does everything for you, perfectwedding.io is truly a game changer.",
            ],
            [
              "image" => "http://localhost:8080/images/testimonials/author-stephanie.jpeg",
              "title" => "Author",
              "author" => "Stephanie",
              "content" => "App worked perfectly, super easy and convenient to use.",
            ],
            [
              "image" => "http://localhost:8080/images/testimonials/ceo-blings-philip.jpeg",
              "title" => "CEO Blings",
              "author" => "Philip",
              "content" => "I have gotten access to the largest customer base ever, making sales on a daily basis, fantastic.",
            ],
            [
              "image" => "http://localhost:8080/images/testimonials/bride-mrs-j.jpeg",
              "title" => "Bride",
              "author" => "Mrs. J",
              "content" => "Planned my whole wedding from my room. Unbelievable.",
            ],
        ]);
    }
}