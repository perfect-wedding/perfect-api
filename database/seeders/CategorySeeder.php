<?php

namespace Database\Seeders;

use App\Models\v1\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Category::truncate();
        if (!Category::exists()) {
            Category::insert([
                [
                    'slug' => \Str::slug('Photography'),
                    'title' => 'Photography',
                    'image' => random_img('images/bank/collect'),
                    'priority' => rand(1, 5),
                    'description' => fake()->sentences(3, true),
                ],
                [
                    'slug' => \Str::slug('Catering'),
                    'title' => 'Catering',
                    'image' => random_img('images/bank/collect'),
                    'priority' => rand(1, 5),
                    'description' => fake()->sentences(3, true),
                ],
                [
                    'slug' => \Str::slug('Decorations'),
                    'title' => 'Decorations',
                    'image' => random_img('images/bank/collect'),
                    'priority' => rand(1, 5),
                    'description' => fake()->sentences(3, true),
                ],
                [
                    'slug' => \Str::slug('Cake makers'),
                    'title' => 'Cake makers',
                    'image' => random_img('images/bank/collect'),
                    'priority' => rand(1, 5),
                    'description' => fake()->sentences(3, true),
                ],
                [
                    'slug' => \Str::slug('Hair and Makeup'),
                    'title' => 'Hair and Makeup',
                    'image' => random_img('images/bank/collect'),
                    'priority' => rand(1, 5),
                    'description' => fake()->sentences(3, true),
                ],
                [
                    'slug' => \Str::slug('Henna designers'),
                    'title' => 'Henna designers',
                    'image' => random_img('images/bank/collect'),
                    'priority' => rand(1, 5),
                    'description' => fake()->sentences(3, true),
                ],
                [
                    'slug' => \Str::slug('Souvenirs'),
                    'title' => 'Souvenirs',
                    'image' => random_img('images/bank/collect'),
                    'priority' => rand(1, 5),
                    'description' => fake()->sentences(3, true),
                ],
                [
                    'slug' => \Str::slug('Accessories'),
                    'title' => 'Accessories',
                    'image' => random_img('images/bank/collect'),
                    'priority' => rand(1, 5),
                    'description' => fake()->sentences(3, true),
                ],
            ]);
        }

        Category::factory()
            ->count(12)
            ->create();
    }
}