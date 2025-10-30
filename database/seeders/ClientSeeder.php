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
        \App\Models\Client::factory(8)->create();

        // Créer quelques clients spécifiques pour les tests avec numéros sénégalais valides
        \App\Models\Client::create([
            'titulaire' => 'Hawa BB Wane',
            'nci' => '1234567890123',
            'email' => 'hawa.wane@example.com',
            'telephone' => '771234567',
            'adresse' => 'Dakar, Sénégal',
            'statut' => 'actif',
        ]);

        \App\Models\Client::create([
            'titulaire' => 'Mamadou Diallo',
            'nci' => '9876543210987',
            'email' => 'mamadou.diallo@example.com',
            'telephone' => '701234567',
            'adresse' => 'Saint-Louis, Sénégal',
            'statut' => 'actif',
        ]);

        \App\Models\Client::create([
            'titulaire' => 'Fatou Sow',
            'nci' => '4567891234567',
            'email' => 'fatou.sow@example.com',
            'telephone' => '781234567',
            'adresse' => 'Thiès, Sénégal',
            'statut' => 'actif',
        ]);

        \App\Models\Client::create([
            'titulaire' => 'Cheikh Ndiaye',
            'nci' => '7891234567890',
            'email' => 'cheikh.ndiaye@example.com',
            'telephone' => '761234567',
            'adresse' => 'Kaolack, Sénégal',
            'statut' => 'inactif',
        ]);
    }
}
