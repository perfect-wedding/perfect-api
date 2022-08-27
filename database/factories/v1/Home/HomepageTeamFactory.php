<?php

namespace Database\Factories\v1\Home;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\v1\Home\HomepageTeam>
 */
class HomepageTeamFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        $username = $this->faker->username();

        return [
            'name' => [$this->faker->name(), $this->faker->firstname()][rand(0, 1)],
            'role' => $this->faker->jobTitle(),
            'info' => $this->faker->sentences(rand(1, 3), true),
            'image' => 'http://localhost:8080/images/dummy-team.jpg',
            'socials' => [
                ['type' => 'facebook', 'username' => $username],
                ['type' => 'twitter', 'username' => $username],
                ['type' => 'instagram', 'username' => $username],
                ['type' => 'linkedin', 'username' => $username],
            ],
        ];
    }
}
