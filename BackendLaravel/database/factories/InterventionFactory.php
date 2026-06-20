<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Intervention;
use App\Models\Moto;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Intervention>
 */
class InterventionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'client_id' => Client::inRandomOrder()->value('id') ?? Client::factory(),
            'moto_id' => Moto::inRandomOrder()->value('id'),
            'technicien_id' => User::role(User::ROLE_SAV)->inRandomOrder()->value('id'),
            'probleme' => fake()->randomElement([
                'Frein arrière défaillant',
                'Moteur qui cale au ralenti',
                'Phare avant ne s\'allume plus',
                'Vidange et entretien périodique',
                'Pneu avant à remplacer',
                'Démarreur électrique HS',
                'Fuite d\'huile moteur',
            ]),
            'statut' => fake()->randomElement(Intervention::STATUTS),
            'date_intervention' => fake()->dateTimeBetween('-10 days', 'now')->format('Y-m-d'),
        ];
    }

    public function nouvelle(): static
    {
        return $this->state(fn () => ['statut' => Intervention::STATUT_NOUVELLE]);
    }

    public function duJour(): static
    {
        return $this->state(fn () => ['date_intervention' => now()->format('Y-m-d')]);
    }
}
