<?php

namespace Database\Factories\v1;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\v1\Bulletin>
 */
class BulletinFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        $audience = [
            ['all'],
            ['concierge'],
            ['provider'],
            ['vendor'],
            ['concierge', 'provider'],
            ['vendor', 'provider'],
            ['vendor', 'concierge'],
            ['vendor', 'concierge', 'provider'],
        ];

        return [
            'title' => $this->faker->sentence,
            'subtitle' => $this->faker->sentence,
            'slug' => $this->faker->slug,
            'content' => $this->faker->paragraph,
            'audience' => $audience[array_rand($audience)],
            'expires_at' => $this->faker->dateTimeBetween('+1 week', '+1 month'),
            'media' => random_img('images/bank/collect'),
            'active' => $this->faker->boolean,
        ];
    }
}
