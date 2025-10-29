<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = $this->faker->randomElement(['depot', 'retrait', 'virement', 'transfert', 'frais']);

        return [
            'numero_transaction' => null, // Sera généré automatiquement par le mutator
            'type' => $type,
            'montant' => $this->faker->randomFloat(2, 100, 100000),
            'devise' => 'FCFA',
            'description' => $this->faker->sentence(),
            'statut' => $this->faker->randomElement(['en_attente', 'validee', 'rejete', 'annulee']),
            'date_transaction' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'compte_id' => \App\Models\Compte::factory(),
            'compte_destination_id' => in_array($type, ['virement', 'transfert'])
                ? \App\Models\Compte::factory()
                : null,
        ];
    }

    /**
     * Indicate that the transaction is validee.
     */
    public function validee(): static
    {
        return $this->state(fn (array $attributes) => [
            'statut' => 'validee',
        ]);
    }

    /**
     * Indicate that the transaction is a depot.
     */
    public function depot(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'depot',
            'compte_destination_id' => null,
        ]);
    }

    /**
     * Indicate that the transaction is a retrait.
     */
    public function retrait(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'retrait',
            'compte_destination_id' => null,
        ]);
    }

    /**
     * Indicate that the transaction is a virement.
     */
    public function virement(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'virement',
            'compte_destination_id' => \App\Models\Compte::factory(),
        ]);
    }

    /**
     * Create transaction for specific compte.
     */
    public function forCompte(\App\Models\Compte $compte): static
    {
        return $this->state(fn (array $attributes) => [
            'compte_id' => $compte->id,
        ]);
    }
}
