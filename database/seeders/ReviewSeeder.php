<?php

namespace Database\Seeders;

use App\Models\v1\Order;
use App\Models\v1\Review;
use Illuminate\Database\Seeder;

class ReviewSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Review::truncate();
        Review::factory()
            ->count(Order::count())
            ->create();
    }
}
