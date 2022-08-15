<?php

namespace Database\Seeders;

use App\Models\v1\Service;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Service::truncate();
        Service::factory()
            ->count(135)
            ->create();
    }
}
