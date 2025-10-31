<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ClientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Créer seulement 8 clients aléatoires (pas trop)
        // En production, éviter Faker et créer des données statiques
        if (app()->environment('production')) {
            // Créer des clients statiques pour la production
            for ($i = 1; $i <= 8; $i++) {
                \App\Models\Client::firstOrCreate(
                    ['email' => "client{$i}@example.com"],
                    [
                        'titulaire' => "Client Test {$i}",
                        'nci' => str_pad($i, 13, '0', STR_PAD_LEFT),
                        'email' => "client{$i}@example.com",
                        'telephone' => "77123456{$i}",
                        'adresse' => "Adresse {$i}, Dakar, Sénégal",
                        'statut' => 'actif',
                        'password' => bcrypt('password123'),
                        'email_verified_at' => now(),
                    ]
                );
            }
        } else {
            // En développement, utiliser Faker
            \App\Models\Client::factory(8)->create([
                'password' => bcrypt('password123'),
            ]);
        }

        // Créer quelques clients spécifiques pour les tests avec numéros sénégalais valides
        // Utilise firstOrCreate pour éviter les doublons
        \App\Models\Client::firstOrCreate(
            ['email' => 'hawa.wane@example.com'],
            [
                'titulaire' => 'Hawa BB Wane',
                'nci' => '1234567890123',
                'email' => 'hawa.wane@example.com',
                'telephone' => '771234567',
                'adresse' => 'Dakar, Sénégal',
                'statut' => 'actif',
                'password' => bcrypt('password123'),
                'email_verified_at' => now(),
            ]
        );

        \App\Models\Client::firstOrCreate(
            ['email' => 'mamadou.diallo@example.com'],
            [
                'titulaire' => 'Mamadou Diallo',
                'nci' => '9876543210987',
                'email' => 'mamadou.diallo@example.com',
                'telephone' => '701234567',
                'adresse' => 'Saint-Louis, Sénégal',
                'statut' => 'actif',
                'password' => bcrypt('password123'),
                'email_verified_at' => now(),
            ]
        );

        \App\Models\Client::firstOrCreate(
            ['email' => 'fatou.sow@example.com'],
            [
                'titulaire' => 'Fatou Sow',
                'nci' => '4567891234567',
                'email' => 'fatou.sow@example.com',
                'telephone' => '781234567',
                'adresse' => 'Thiès, Sénégal',
                'statut' => 'actif',
                'password' => bcrypt('password123'),
                'email_verified_at' => now(),
            ]
        );

        \App\Models\Client::firstOrCreate(
            ['email' => 'cheikh.ndiaye@example.com'],
            [
                'titulaire' => 'Cheikh Ndiaye',
                'nci' => '7891234567890',
                'email' => 'cheikh.ndiaye@example.com',
                'telephone' => '761234567',
                'adresse' => 'Kaolack, Sénégal',
                'statut' => 'inactif',
                'password' => bcrypt('password123'),
                'email_verified_at' => now(),
            ]
        );
    }
}
