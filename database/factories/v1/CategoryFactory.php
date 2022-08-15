<?php

namespace Database\Factories\v1;

use Illuminate\Database\Eloquent\Factories\Factory;

class CategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $title = $this->faker->words(rand(3, 5), true);

        return [
            'slug' => \Str::slug($title),
            'title' => ucwords($title),
            'image' => random_img('images/bank/collect'),
            'priority' => rand(1, 5),
            'description' => $this->faker->sentences(3, true),
        ];
    }
}
