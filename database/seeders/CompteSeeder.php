<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Compte;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Hash;

class CompteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Compatible avec les environnements local et production.
     * Utilise Faker en développement, données statiques en production.
     */
    public function run(): void
    {
        try {
            // Créer des comptes de test spécifiques (communs à tous les environnements)
            $this->createTestComptes();

            // Créer des comptes supplémentaires selon l'environnement
            if (App::environment('production')) {
                $this->createProductionComptes();
            } else {
                $this->createDevelopmentComptes();
            }

            $totalComptes = Compte::count();
            $this->command->info("🎉 Seeding Compte terminé avec succès ! {$totalComptes} comptes présents en base.");

        } catch (\Exception $e) {
            $this->command->error("❌ Erreur lors du seeding des comptes : {$e->getMessage()}");
            throw $e;
        }
    }

    /**
     * Créer des comptes de test spécifiques (présents dans tous les environnements)
     */
    private function createTestComptes(): void
    {
        $this->command->info('🧪 Création des comptes de test...');

        $testComptes = [
            [
                'numero_compte' => 'CB241031000001',
                'type' => 'epargne',
                'solde_initial' => 500000,
                'devise' => 'FCFA',
                'statut' => 'actif',
                'client_email' => 'hawa.wane@example.com',
            ],
            [
                'numero_compte' => 'CB241031000002',
                'type' => 'courant',
                'solde_initial' => 1000000,
                'devise' => 'FCFA',
                'statut' => 'actif',
                'client_email' => 'mamadou.diallo@example.com',
            ],
            [
                'numero_compte' => 'CB241031000003',
                'type' => 'epargne',
                'solde_initial' => 1500000,
                'devise' => 'FCFA',
                'statut' => 'actif',
                'client_email' => 'fatou.sow@example.com',
            ],
            [
                'numero_compte' => 'CB241031000004',
                'type' => 'courant',
                'solde_initial' => 200000,
                'devise' => 'FCFA',
                'statut' => 'inactif',
                'client_email' => 'cheikh.ndiaye@example.com',
            ],
        ];

        foreach ($testComptes as $compteData) {
            try {
                $client = Client::where('email', $compteData['client_email'])->first();

                if (!$client) {
                    $this->command->warn("⚠️ Client {$compteData['client_email']} non trouvé, compte ignoré.");
                    continue;
                }

                // Créer le compte avec seulement les colonnes valides
                $compte = Compte::firstOrCreate(
                    ['numero_compte' => $compteData['numero_compte']],
                    [
                        'numero_compte' => $compteData['numero_compte'],
                        'type' => $compteData['type'],
                        'solde_initial' => $compteData['solde_initial'],
                        'devise' => $compteData['devise'],
                        'statut' => $compteData['statut'],
                        'client_id' => $client->id,
                    ]
                );

                if ($compte->wasRecentlyCreated) {
                    $this->command->info("✅ Compte de test créé : {$compteData['numero_compte']} pour {$client->titulaire}");
                } else {
                    $this->command->info("ℹ️ Compte de test déjà existant : {$compteData['numero_compte']} pour {$client->titulaire}");
                }
            } catch (\Exception $e) {
                $this->command->error("❌ Erreur création compte de test {$compteData['numero_compte']} : {$e->getMessage()}");
            }
        }
    }

    /**
     * Créer des comptes pour l'environnement de production
     * Utilise des données statiques pour éviter toute dépendance
     */
    private function createProductionComptes(): void
    {
        $this->command->info('🌍 Environnement de production détecté - Création de comptes statiques...');

        $targetCount = 8; // Nombre total souhaité en plus des comptes de test
        $existingCount = Compte::count();

        if ($existingCount < $targetCount) {
            $additionalComptesCount = $targetCount - $existingCount;

            // Récupérer les clients disponibles
            $clients = Client::where('statut', 'actif')->get();

            if ($clients->isEmpty()) {
                $this->command->warn('⚠️ Aucun client actif trouvé, création de comptes annulée.');
                return;
            }

            for ($i = 1; $i <= $additionalComptesCount; $i++) {
                try {
                    $client = $clients->random();
                    $numeroCompte = 'CB' . now()->format('ym') . str_pad($i + 1000, 8, '0', STR_PAD_LEFT);

                    $compte = Compte::firstOrCreate(
                        ['numero_compte' => $numeroCompte],
                        [
                            'numero_compte' => $numeroCompte,
                            'type' => collect(['epargne', 'courant', 'cheque'])->random(),
                            'solde_initial' => rand(100000, 5000000),
                            'devise' => 'FCFA',
                            'statut' => collect(['actif', 'inactif'])->random(),
                            'client_id' => $client->id,
                        ]
                    );

                    if ($compte->wasRecentlyCreated) {
                        $this->command->info("✅ Compte statique créé : {$numeroCompte} pour {$client->titulaire}");
                    } else {
                        $this->command->info("ℹ️ Compte statique déjà existant : {$numeroCompte} pour {$client->titulaire}");
                    }
                } catch (\Exception $e) {
                    $this->command->error("❌ Erreur création compte statique {$i} : {$e->getMessage()}");
                }
            }
        } else {
            $this->command->info("ℹ️ Nombre de comptes suffisant ({$existingCount}), pas de création supplémentaire.");
        }
    }

    /**
     * Créer des comptes pour l'environnement de développement
     * Utilise Faker si disponible, sinon données statiques
     */
    private function createDevelopmentComptes(): void
    {
        $this->command->info('🏠 Environnement de développement détecté - Création de comptes...');

        $targetCount = 12; // Nombre total souhaité (4 de test + 8 supplémentaires)
        $existingCount = Compte::count();

        if ($existingCount < $targetCount) {
            $additionalComptesCount = $targetCount - $existingCount;
            $this->command->info("🎲 Création de {$additionalComptesCount} comptes supplémentaires...");

            // Vérifier si Faker est disponible
            if ($this->isFakerAvailable()) {
                try {
                    // Utiliser Faker en développement
                    $clients = Client::all();

                    if ($clients->isEmpty()) {
                        $this->command->warn('⚠️ Aucun client trouvé, création de comptes annulée.');
                        return;
                    }

                    foreach ($clients as $client) {
                        // Créer 1-2 comptes par client
                        $compteCount = rand(1, 2);

                        for ($i = 0; $i < $compteCount && $additionalComptesCount > 0; $i++) {
                            Compte::factory()->forClient($client)->create();
                            $additionalComptesCount--;
                        }

                        if ($additionalComptesCount <= 0) break;
                    }

                    $createdComptes = Compte::latest()->take(min(8, Compte::count()))->get();
                    foreach ($createdComptes as $compte) {
                        $this->command->info("✅ Compte Faker créé : {$compte->numero_compte} pour {$compte->client->titulaire}");
                    }
                } catch (\Exception $e) {
                    $this->command->warn("⚠️ Faker indisponible, création de comptes statiques : {$e->getMessage()}");
                    $this->createStaticComptes($additionalComptesCount);
                }
            } else {
                $this->command->info("ℹ️ Faker non disponible, création de comptes statiques...");
                $this->createStaticComptes($additionalComptesCount);
            }
        } else {
            $this->command->info("ℹ️ Nombre de comptes suffisant ({$existingCount}), pas de création supplémentaire.");
        }
    }

    /**
     * Créer des comptes statiques (sans Faker)
     */
    private function createStaticComptes(int $count): void
    {
        // Récupérer les clients disponibles
        $clients = Client::where('statut', 'actif')->get();

        if ($clients->isEmpty()) {
            $this->command->warn('⚠️ Aucun client actif trouvé, création de comptes annulée.');
            return;
        }

        for ($i = 1; $i <= $count; $i++) {
            try {
                $client = $clients->random();
                $numeroCompte = 'CB' . now()->format('ym') . str_pad($i + 2000, 8, '0', STR_PAD_LEFT);

                $compte = Compte::firstOrCreate(
                    ['numero_compte' => $numeroCompte],
                    [
                        'numero_compte' => $numeroCompte,
                        'type' => collect(['epargne', 'courant', 'cheque'])->random(),
                        'solde_initial' => rand(100000, 5000000),
                        'devise' => 'FCFA',
                        'statut' => collect(['actif', 'inactif'])->random(),
                        'client_id' => $client->id,
                    ]
                );

                if ($compte->wasRecentlyCreated) {
                    $this->command->info("✅ Compte statique créé : {$numeroCompte} pour {$client->titulaire}");
                } else {
                    $this->command->info("ℹ️ Compte statique déjà existant : {$numeroCompte} pour {$client->titulaire}");
                }
            } catch (\Exception $e) {
                $this->command->error("❌ Erreur création compte statique {$i} : {$e->getMessage()}");
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
