<?php

namespace Database\Factories;

use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Client>
 */
class ClientFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nom' => fake()->name(),
            'telephone' => '+228 ' . fake()->numerify('## ## ## ##'),
            'email' => fake()->optional()->safeEmail(),
            'adresse' => fake()->city(),
        ];
    }
}
