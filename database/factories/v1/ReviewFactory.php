<?php

namespace Database\Factories\v1;

use App\Models\v1\Order;
use App\Models\v1\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReviewFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $order = Order::inRandomOrder()->first();

        return [
            'user_id' => User::inRandomOrder()->first()->id,
            'reviewable_type' => $order->orderable_type,
            'reviewable_id' => $order->orderable_id,
            'comment' => $this->faker->text(),
            'rating' => rand(1.3, 5.0),
            'featured' => [true, false][rand(0, 1)],
        ];
    }
}
