<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $faker = $this->faker;

        return [
            'firstname' => $faker->firstname,
            'lastname' => $faker->lastname,
            'username' => $faker->username,
            'address' => $faker->address,
            'intro' => $faker->text(50),
            'about' => $faker->text,
            'dob' => $faker->date,
            'phone' => $faker->phoneNumber,
            'email' => $faker->unique()->safeEmail,
            'email_verified_at' => now(),
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
            'remember_token' => Str::random(10),
            'role' => ['user', 'vendor', 'provider', 'concierge'][rand(0, 3)],
            'type' => ['individual', 'company'][rand(0, 1)],
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function unverified()
    {
        return $this->state(function (array $attributes) {
            return [
                'email_verified_at' => null,
            ];
        });
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
        ->afterCreating(function (\App\Models\User $user) {
            if ($user->type === 'company') {
                $faker = $this->faker;
                $company = new \App\Models\v1\Company;
                $company->user_id = $user->id;
                $company->slug = $faker->slug();
                $company->name = $faker->company();
                $company->city = $faker->city();
                $company->postal = $faker->postcode();
                $company->address = $faker->address();
                $company->country = $faker->country();
                $company->status = 'verified';
                $company->save();
            }
        });
    }
}
