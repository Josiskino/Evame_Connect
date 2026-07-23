<?php

namespace Database\Factories;

use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Client>
 */
class ClientFactory extends Factory
{
    /** Quartiers / villes du Togo pour des adresses réalistes. */
    public const ADRESSES_TOGO = [
        'Tokoin, Lomé', 'Bè, Lomé', 'Adidogomé, Lomé', 'Agoè-Nyivé, Lomé',
        'Nyékonakpoè, Lomé', 'Hédzranawoé, Lomé', 'Baguida, Lomé', 'Kodjoviakopé, Lomé',
        'Avédji, Lomé', 'Cacavéli, Lomé', 'Kara', 'Sokodé', 'Kpalimé', 'Atakpamé',
        'Dapaong', 'Tsévié', 'Aného', 'Bassar', 'Notsé', 'Vogan',
    ];

    /** Préfectures où une CNI peut être émise. */
    public const LIEUX_EMISSION = ['Lomé', 'Kara', 'Sokodé', 'Kpalimé', 'Atakpamé', 'Dapaong'];

    public function definition(): array
    {
        // CNI valide : émise dans le passé, expire dans le futur.
        $emission = fake()->dateTimeBetween('-4 years', '-6 months');
        $expiration = (clone $emission)->modify('+10 years');

        return [
            'nom' => fake()->name(),
            // Format normalisé (228XXXXXXXX) : identifiant de connexion OTP côté client.
            'telephone' => '228'.fake()->unique()->numerify('########'),
            'email' => fake()->optional()->safeEmail(),
            'adresse' => fake()->randomElement(self::ADRESSES_TOGO),
            'cni_recto' => null,
            'cni_verso' => null,
            'cni_date_emission' => $emission->format('Y-m-d'),
            'cni_date_expiration' => $expiration->format('Y-m-d'),
            'cni_lieu_emission' => fake()->randomElement(self::LIEUX_EMISSION),
        ];
    }
}
