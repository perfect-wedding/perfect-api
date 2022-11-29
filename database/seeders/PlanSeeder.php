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
                'slug' => Str::of('Business')->slug(),
                'title' => 'Business',
                'basic_info' => 'For small businesses',
                'price' => 1000,
                'features' => json_encode(['All Free Features']),
                'popular' => false,
                'icon' => 'fas fa-circle-star',
                'trial_days' => 0,
                'duration' => 30,
                'tenure' => 'daily',
                'type' => null,
                'meta' => collect(),
                'annual' => false,
            ],
            [
                'slug' => Str::of('Enterprise')->slug(),
                'title' => 'Enterprise',
                'basic_info' => 'All the features you need to grow your business',
                'price' => 5000,
                'features' => json_encode(['All Business Features', 'Priority Support']),
                'popular' => true,
                'icon' => 'fas fa-stars',
                'trial_days' => 0,
                'duration' => 30,
                'tenure' => 'daily',
                'type' => null,
                'meta' => collect(),
                'annual' => false,
            ],
            [
                'slug' => Str::of('Service Plan')->slug(),
                'title' => 'Service Plan',
                'basic_info' => 'Specifically designed for service providers to show their services to a wider audience.',
                'price' => 5000,
                'features' => json_encode(['Show your service to the world', 'Get more customers', 'Get more orders', 'Raise more revenue']),
                'popular' => false,
                'icon' => 'fas fa-store',
                'trial_days' => 0,
                'duration' => 31,
                'tenure' => 'daily',
                'type' => 'featured',
                'meta' => collect(['type' => 'service', 'places' => ['marketplace', 'warehouse', 'giftshop']]),
                'annual' => false,
            ],
            [
                'slug' => Str::of('Business Plan')->slug(),
                'title' => 'Business Plan',
                'basic_info' => 'We will help make more people aware of your business and get you more customers.',
                'price' => 1000,
                'features' => json_encode(['Show your business to the world', 'Get more customers', 'Get more orders', 'Raise more revenue']),
                'popular' => true,
                'icon' => 'fas fa-check-circle',
                'trial_days' => 0,
                'duration' => 31,
                'tenure' => 'daily',
                'type' => 'featured',
                'meta' => collect(['type' => 'company', 'places' => ['marketplace', 'warehouse', 'giftshop']]),
                'annual' => false,
            ],
            [
                'slug' => Str::of('Product Plan')->slug(),
                'title' => 'Product Plan',
                'basic_info' => 'Specifically designed to help make more sales for your product.',
                'price' => 11000,
                'features' => json_encode(['Show your product to the world', 'Get more customers', 'Make more sales', 'Raise more revenue']),
                'popular' => false,
                'icon' => 'fas fa-shopping-cart',
                'trial_days' => 0,
                'duration' => 31,
                'tenure' => 'daily',
                'type' => 'featured',
                'meta' => collect(['type' => 'inventory', 'places' => ['marketplace', 'warehouse', 'giftshop']]),
                'annual' => false,
            ]
        ], ['id']);
    }
}
