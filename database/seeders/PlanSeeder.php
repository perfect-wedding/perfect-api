<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PlanSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        \App\Models\v1\Plan::upsert([
            [
                'slug' => Str::of('Free Forever')->slug(),
                'title' => 'Free Forever',
                'price' => 0,
                'features' => json_encode(['Free Contacts', 'Support']),
                'popular' => false,
            ],
            [
                'slug' => Str::of('Enterprise')->slug(),
                'title' => 'Enterprise',
                'price' => 5000,
                'features' => json_encode(['All Enterprise Features', 'Priority Support']),
                'popular' => true,
            ],
            [
                'slug' => Str::of('Business')->slug(),
                'title' => 'Business',
                'price' => 1000,
                'features' => json_encode(['All Free Features']),
                'popular' => false,
            ],
        ], ['id']);
    }
}
