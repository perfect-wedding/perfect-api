<?php

namespace Database\Factories\v1;

use App\Models\v1\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CompanyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $title = $this->faker->words(rand(3, 4), true);

        return [
            'user_id' => User::inRandomOrder()->first()->id,
            'slug' => str($title)->slug(),
            'name' => $title,
            'type' => ['provider', 'vendor'][rand(0, 1)],
            'intro' => $this->faker->sentence(),
            'about' => $this->faker->text(550),
            'logo' => random_img('images/bank/collect'),
            'banner' => random_img('images/bank/collect'),
            'email' => $this->faker->email(),
            'phone' => $this->faker->phoneNumber(),
            'address' => $this->faker->address(),
            'country' => $this->faker->country(),
            'state' => $this->faker->state(),
            'city' => $this->faker->city(),
            'postal' => $this->faker->postcode(),
        ];
    }

    /**
     * Configure the model factory.
     *
     * @return $this
     */
    public function configure()
    {
        return $this->afterMaking(function () {
        })
            ->afterCreating(function (\App\Models\v1\Company $company) {
                $user = $company->user;
                $user->company_id = $company->id;
                $user->save();
            });
    }
}
