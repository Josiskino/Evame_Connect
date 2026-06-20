<?php

namespace Database\Factories;

use App\Models\Moto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Moto>
 */
class MotoFactory extends Factory
{
    public function definition(): array
    {
        $cylindree = fake()->randomElement(['110 CC', '125 CC', '150 CC', '200 CC']);

        return [
            'modele' => 'EVAME ' . $cylindree,
            'couleur' => fake()->randomElement(['Noir', 'Rouge', 'Bleu', 'Blanc', 'Gris']),
            'cylindree' => $cylindree,
            'prix' => fake()->numberBetween(350_000, 1_200_000),
            'image_url' => null,
            'stock' => fake()->numberBetween(0, 20),
            'seuil_alerte' => 3,
        ];
    }

    /** Moto en rupture de stock. */
    public function rupture(): static
    {
        return $this->state(fn () => ['stock' => 0]);
    }

    /** Moto en alerte de stock faible. */
    public function stockFaible(): static
    {
        return $this->state(fn () => ['stock' => 2, 'seuil_alerte' => 3]);
    }
}
