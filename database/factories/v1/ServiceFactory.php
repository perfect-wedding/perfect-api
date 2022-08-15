<?php

namespace Database\Factories\v1;

use App\Models\v1\Category;
use App\Models\v1\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\v1\Inventory>
 */
class ServiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        $company = Company::inRandomOrder()->first();
        $title = $this->faker->words(2, true);

        return [
            'user_id' => $company->user->id,
            'slug' => str($title)->slug(),
            'category_id' => Category::inRandomOrder()->first()->id,
            'company_id' => $company->id,
            'price' => rand(10000, 99999),
            'stock' => rand(5, 20),
            'title' => $title,
            'basic_info' => $this->faker->sentence(7),
            'short_desc' => $this->faker->text(75),
            'details' => $this->faker->text(550),
            'image' => random_img('images/bank/collect'),
            'price' => rand(100, 500),
        ];
    }
}
