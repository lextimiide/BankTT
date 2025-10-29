<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Compte>
 */
class CompteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'numero_compte' => null, // Sera généré automatiquement par le mutator
            'type' => $this->faker->randomElement(['cheque', 'epargne', 'courant']),
            'solde_initial' => $this->faker->randomFloat(2, 1000, 1000000),
            'devise' => 'FCFA',
            'statut' => $this->faker->randomElement(['actif', 'inactif', 'bloque', 'ferme']),
            'client_id' => \App\Models\Client::factory(),
        ];
    }

    /**
     * Indicate that the compte is actif.
     */
    public function actif(): static
    {
        return $this->state(fn (array $attributes) => [
            'statut' => 'actif',
        ]);
    }

    /**
     * Indicate that the compte is a cheque account.
     */
    public function cheque(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'cheque',
        ]);
    }

    /**
     * Indicate that the compte is an epargne account.
     */
    public function epargne(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'epargne',
        ]);
    }

    /**
     * Create compte with specific client.
     */
    public function forClient(\App\Models\Client $client): static
    {
        return $this->state(fn (array $attributes) => [
            'client_id' => $client->id,
        ]);
    }
}
