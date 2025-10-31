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
        // Seed des admins (nécessaires pour l'authentification)
        $this->call([
            AdminSeeder::class,
        ]);

        // Seed des clients de test
        $this->call([
            ClientSeeder::class,
            CompteSeeder::class,
        ]);

        // Note: Les utilisateurs classiques ne sont pas seedés car ce projet bancaire utilise
        // uniquement les modèles Admin et Client pour l'authentification
    }
}
