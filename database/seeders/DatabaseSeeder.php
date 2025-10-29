<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed des clients de test
        $this->call([
            ClientSeeder::class,
            CompteSeeder::class,
        ]);

        // Note: Les utilisateurs ne sont pas seedés car le modèle User n'existe pas dans ce projet bancaire
        // Seuls les clients et comptes sont nécessaires pour les tests
    }
}
