<?php

namespace Database\Factories\v1;

use App\Models\v1\Category;
use App\Models\v1\Company;
use App\Traits\Meta;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\v1\Inventory>
 */
class InventoryFactory extends Factory
{
    use Meta;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        $company = Company::verified()->whereType('vendor')->inRandomOrder()->first();
        $title = $this->faker->words(rand(2, 3), true);

        return [
            'user_id' => $company->user->id,
            'slug' => str($title)->slug(),
            'category_id' => Category::whereType('warehouse')->inRandomOrder()->first()->id,
            'company_id' => $company->id,
            'price' => rand(10000, 99999),
            'stock' => rand(5, 20),
            'name' => $title,
            'type' => 'market',
            'details' => $this->faker->text(550),
            'image' => random_img('images/bank/collect'),
            'code' => str($company->name)->limit(2, '')->prepend(str('DUMMY')->append($this->generate_string(6, 3)))->upper(),
            'price' => rand(100, 5000),
        ];
    }
}
