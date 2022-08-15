<?php

namespace Database\Factories\v1;

use App\Models\v1\Inventory;
use App\Models\v1\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

class OfferFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $item = [Service::inRandomOrder()->first(), Inventory::inRandomOrder()->first()][rand(0, 1)];
        $operator = ['%', '-', '+', '*'][rand(0, 3)];
        $seed = rand(0, 1);
        $amt = rand(100, 10000);
        $amount = ($seed === 0 || $operator == '*' || $operator == '%'
            ? ($operator == '*' ? rand(10, 45) : rand(5, 100))
            : ($amt >= $item->price
                ? ($amt / $item->price) + 5
                : $amt
        ));
        $featured = $item->offers()->where('featured', true);
        $desc = array_merge([
            'Makeup for the bridal party or entourage (5 people)',
            $amount.$operator.' Discount',
            'Motor Convoy of 30 Mercedes e320',
            'Priority services for real delivery',
        ], $this->faker->sentences());

        return [
            'offerable_id' => $item->id,
            'offerable_type' => get_class($item),
            'title' => ['Premium', 'Classic', 'Social', 'Mini', 'Discount'][rand(0, 4)],
            'description' => $desc[rand(0, count($desc) - 1)],
            'type' => ['discount', 'increase'][$seed],
            'operator' => $operator,
            'featured' => ! $featured->exists() ? $seed : false,
            'amount' => $amount,
        ];
    }
}
