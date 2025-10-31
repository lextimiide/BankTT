<?php

namespace Database\Seeders;

use App\Models\Client;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Hash;

class ClientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Compatible avec les environnements local et production.
     * Utilise Faker en développement, données statiques en production.
     */
    public function run(): void
    {
        try {
            // Créer des clients de test spécifiques (communs à tous les environnements)
            $this->createTestClients();

            // Créer des clients supplémentaires selon l'environnement
            if (App::environment('production')) {
                $this->createProductionClients();
            } else {
                $this->createDevelopmentClients();
            }

            $totalClients = Client::count();
            $this->command->info("🎉 Seeding Client terminé avec succès ! {$totalClients} clients présents en base.");

        } catch (\Exception $e) {
            $this->command->error("❌ Erreur lors du seeding des clients : {$e->getMessage()}");
            throw $e;
        }
    }

    /**
     * Créer des clients de test spécifiques (présents dans tous les environnements)
     */
    private function createTestClients(): void
    {
        $this->command->info('🧪 Création des clients de test...');

        $testClients = [
            [
                'titulaire' => 'Hawa BB Wane',
                'nci' => '1234567890123',
                'email' => 'hawa.wane@example.com',
                'telephone' => '771234567',
                'adresse' => 'Dakar, Sénégal',
                'statut' => 'actif',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ],
            [
                'titulaire' => 'Mamadou Diallo',
                'nci' => '9876543210987',
                'email' => 'mamadou.diallo@example.com',
                'telephone' => '701234567',
                'adresse' => 'Saint-Louis, Sénégal',
                'statut' => 'actif',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ],
            [
                'titulaire' => 'Fatou Sow',
                'nci' => '4567891234567',
                'email' => 'fatou.sow@example.com',
                'telephone' => '781234567',
                'adresse' => 'Thiès, Sénégal',
                'statut' => 'actif',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ],
            [
                'titulaire' => 'Cheikh Ndiaye',
                'nci' => '7891234567890',
                'email' => 'cheikh.ndiaye@example.com',
                'telephone' => '761234567',
                'adresse' => 'Kaolack, Sénégal',
                'statut' => 'inactif',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ],
        ];

        foreach ($testClients as $clientData) {
            try {
                $client = Client::firstOrCreate(
                    ['email' => $clientData['email']],
                    $clientData
                );

                if ($client->wasRecentlyCreated) {
                    $this->command->info("✅ Client de test créé : {$clientData['titulaire']} ({$clientData['email']})");
                } else {
                    $this->command->info("ℹ️ Client de test déjà existant : {$clientData['titulaire']} ({$clientData['email']})");
                }
            } catch (\Exception $e) {
                $this->command->error("❌ Erreur création client de test {$clientData['email']} : {$e->getMessage()}");
            }
        }
    }

    /**
     * Créer des clients pour l'environnement de production
     * Utilise des données statiques pour éviter toute dépendance
     */
    private function createProductionClients(): void
    {
        $this->command->info('🌍 Environnement de production détecté - Création de clients statiques...');

        $targetCount = 8; // Nombre total souhaité en plus des clients de test
        $existingCount = Client::count();

        if ($existingCount < $targetCount) {
            $additionalClientsCount = $targetCount - $existingCount;

            for ($i = 1; $i <= $additionalClientsCount; $i++) {
                try {
                    $client = Client::firstOrCreate(
                        ['email' => "client{$i}@example.com"],
                        [
                            'titulaire' => "Client Test {$i}",
                            'nci' => str_pad($i, 13, '0', STR_PAD_LEFT),
                            'email' => "client{$i}@example.com",
                            'telephone' => "77123456{$i}",
                            'adresse' => "Adresse {$i}, Dakar, Sénégal",
                            'statut' => 'actif',
                            'password' => Hash::make('password123'),
                            'email_verified_at' => now(),
                        ]
                    );

                    if ($client->wasRecentlyCreated) {
                        $this->command->info("✅ Client statique créé : Client Test {$i} (client{$i}@example.com)");
                    } else {
                        $this->command->info("ℹ️ Client statique déjà existant : Client Test {$i} (client{$i}@example.com)");
                    }
                } catch (\Exception $e) {
                    $this->command->error("❌ Erreur création client statique {$i} : {$e->getMessage()}");
                }
            }
        } else {
            $this->command->info("ℹ️ Nombre de clients suffisant ({$existingCount}), pas de création supplémentaire.");
        }
    }

    /**
     * Créer des clients pour l'environnement de développement
     * Utilise Faker si disponible, sinon données statiques
     */
    private function createDevelopmentClients(): void
    {
        $this->command->info('🏠 Environnement de développement détecté - Création de clients...');

        $targetCount = 12; // Nombre total souhaité (4 de test + 8 supplémentaires)
        $existingCount = Client::count();

        if ($existingCount < $targetCount) {
            $additionalClientsCount = $targetCount - $existingCount;
            $this->command->info("🎲 Création de {$additionalClientsCount} clients supplémentaires...");

            // Vérifier si Faker est disponible
            if ($this->isFakerAvailable()) {
                try {
                    // Utiliser Faker en développement
                    Client::factory($additionalClientsCount)->create([
                        'password' => Hash::make('password123'),
                    ]);

                    foreach (Client::latest()->take($additionalClientsCount)->get() as $client) {
                        $this->command->info("✅ Client Faker créé : {$client->titulaire} ({$client->email})");
                    }
                } catch (\Exception $e) {
                    $this->command->warn("⚠️ Faker indisponible, création de clients statiques : {$e->getMessage()}");
                    $this->createStaticClients($additionalClientsCount);
                }
            } else {
                $this->command->info("ℹ️ Faker non disponible, création de clients statiques...");
                $this->createStaticClients($additionalClientsCount);
            }
        } else {
            $this->command->info("ℹ️ Nombre de clients suffisant ({$existingCount}), pas de création supplémentaire.");
        }
    }

    /**
     * Créer des clients statiques (sans Faker)
     */
    private function createStaticClients(int $count): void
    {
        for ($i = 1; $i <= $count; $i++) {
            try {
                $client = Client::firstOrCreate(
                    ['email' => "client{$i}@example.com"],
                    [
                        'titulaire' => "Client Test {$i}",
                        'nci' => str_pad($i, 13, '0', STR_PAD_LEFT),
                        'email' => "client{$i}@example.com",
                        'telephone' => "77123456{$i}",
                        'adresse' => "Adresse {$i}, Dakar, Sénégal",
                        'statut' => 'actif',
                        'password' => Hash::make('password123'),
                        'email_verified_at' => now(),
                    ]
                );

                if ($client->wasRecentlyCreated) {
                    $this->command->info("✅ Client statique créé : Client Test {$i} (client{$i}@example.com)");
                } else {
                    $this->command->info("ℹ️ Client statique déjà existant : Client Test {$i} (client{$i}@example.com)");
                }
            } catch (\Exception $e) {
                $this->command->error("❌ Erreur création client statique {$i} : {$e->getMessage()}");
            }
        }
    }

    /**
     * Vérifier si Faker est disponible
     */
    private function isFakerAvailable(): bool
    {
        return class_exists('\Faker\Factory');
    }
}
