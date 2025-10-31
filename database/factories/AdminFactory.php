<?php

namespace Database\Factories;

use App\Models\Admin;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Admin>
 */
class AdminFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = Admin::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $prenoms = ['Jean', 'Marie', 'Pierre', 'Sophie', 'Michel', 'Isabelle', 'Philippe', 'Nathalie', 'François', 'Catherine'];
        $noms = ['Dubois', 'Martin', 'Bernard', 'Thomas', 'Petit', 'Robert', 'Richard', 'Durand', 'Leroy', 'Moreau'];

        return [
            'nom' => $this->faker->randomElement($noms),
            'prenom' => $this->faker->randomElement($prenoms),
            'email' => $this->faker->unique()->safeEmail(),
            'password' => Hash::make('password123'), // Mot de passe par défaut pour les tests
            'email_verified_at' => $this->faker->optional(0.8)->dateTimeBetween('-1 year', 'now'),
        ];
    }

    /**
     * Indicate that the admin's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Create an admin with a specific role indicator in the name.
     */
    public function manager(): static
    {
        return $this->state(fn (array $attributes) => [
            'prenom' => 'Manager',
            'nom' => $this->faker->randomElement(['Banque', 'Système', 'Opération']),
        ]);
    }

    /**
     * Create a super admin.
     */
    public function superAdmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'prenom' => 'Super',
            'nom' => 'Admin',
        ]);
    }
}
