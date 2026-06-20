<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Moto;
use App\Models\User;
use App\Models\Vente;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vente>
 */
class VenteFactory extends Factory
{
    public function definition(): array
    {
        $moto = Moto::inRandomOrder()->first() ?? Moto::factory()->create();

        return [
            'client_id' => Client::inRandomOrder()->value('id') ?? Client::factory(),
            'moto_id' => $moto->id,
            'user_id' => User::role(User::ROLE_COMMERCIAL)->inRandomOrder()->value('id') ?? User::factory(),
            'mode' => fake()->randomElement([Vente::MODE_DIRECT, Vente::MODE_LEASING]),
            'montant' => $moto->prix,
            'date_vente' => fake()->dateTimeBetween('-6 months', 'now')->format('Y-m-d'),
            'statut' => 'validee',
        ];
    }
}
