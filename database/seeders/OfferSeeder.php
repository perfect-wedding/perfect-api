<?php

namespace Database\Seeders;

use App\Models\v1\Inventory;
use App\Models\v1\Offer;
use App\Models\v1\Service;
use Illuminate\Database\Seeder;

class OfferSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Offer::truncate();
        Offer::factory()
            ->count((Service::count() + Inventory::count()) * 2)
            ->create();
    }
}
