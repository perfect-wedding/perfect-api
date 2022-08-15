<?php

namespace Database\Factories\v1;

use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class MarketFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'user_id' => User::inRandomOrder()->first()->id,
            'category_id' => Category::inRandomOrder()->first()->id,
            'title' => $this->faker->text(50),
            'desc' => $this->faker->text(),
            'details' => $this->faker->text(550),
            'image' => random_img('images/bank/collect'),
            'price' => rand(100, 500),
        ];
    }
}
