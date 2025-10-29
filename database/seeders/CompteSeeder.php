<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CompteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Créer des comptes pour les clients existants (1 compte par client max)
        $clients = \App\Models\Client::all();

        foreach ($clients as $client) {
            // Seulement 1 compte par client pour éviter trop de données
            \App\Models\Compte::factory()->forClient($client)->create();
        }

        // Créer quelques comptes supplémentaires avec des soldes variés
        \App\Models\Compte::factory()->create([
            'type' => 'epargne',
            'solde_initial' => 2000000,
            'devise' => 'FCFA',
            'statut' => 'actif',
        ]);

        \App\Models\Compte::factory()->create([
            'type' => 'courant',
            'solde_initial' => 500000,
            'devise' => 'FCFA',
            'statut' => 'actif',
        ]);

        \App\Models\Compte::factory()->create([
            'type' => 'cheque',
            'solde_initial' => 150000,
            'devise' => 'FCFA',
            'statut' => 'inactif',
        ]);

        \App\Models\Compte::factory()->create([
            'type' => 'epargne',
            'solde_initial' => 750000,
            'devise' => 'FCFA',
            'statut' => 'bloque',
        ]);
    }
}
