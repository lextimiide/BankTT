<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Client>
 */
class ClientFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'titulaire' => fake()->name(),
            'nci' => fake()->optional(0.7)->numerify('#############'), // Numéro de 13 chiffres optionnel
            'email' => fake()->unique()->safeEmail(),
            'telephone' => fake()->numerify('7########'), // Format sénégalais
            'adresse' => fake()->address(),
            'statut' => fake()->randomElement(['actif', 'inactif', 'suspendu']),
            'email_verified_at' => fake()->optional(0.8)->dateTimeBetween('-1 year', 'now'),
        ];
    }

    /**
     * Indicate that the client is actif.
     */
    public function actif(): static
    {
        return $this->state(fn (array $attributes) => [
            'statut' => 'actif',
        ]);
    }

    /**
     * Indicate that the client's email is verified.
     */
    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => now(),
        ]);
    }
}
