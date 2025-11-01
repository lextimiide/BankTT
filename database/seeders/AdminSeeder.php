<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Compatible avec les environnements local et production.
     * Utilise Faker en développement, données statiques en production.
     */
    public function run(): void
    {
        try {
            // Vérifier l'environnement et créer les admins appropriés
            if (App::environment('production')) {
                $this->createProductionAdmins();
            } else {
                $this->createDevelopmentAdmins();
            }

            $totalAdmins = Admin::count();
            $this->command->info("🎉 Seeding Admin terminé avec succès ! {$totalAdmins} admins présents en base.");

        } catch (\Exception $e) {
            $this->command->error("❌ Erreur lors du seeding des admins : {$e->getMessage()}");
            throw $e;
        }
    }

    /**
     * Créer des admins pour l'environnement de production
     * Utilise des données statiques pour éviter toute dépendance à Faker
     */
    private function createProductionAdmins(): void
    {
        $this->command->info('🌍 Environnement de production détecté - Création d\'admins statiques...');

        $defaultAdmins = [
            [
                'nom' => 'Admin',
                'prenom' => 'Super',
                'email' => 'admin@banque.com',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ],
            [
                'nom' => 'Manager',
                'prenom' => 'Banque',
                'email' => 'manager@banque.com',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ],
        ];

        foreach ($defaultAdmins as $adminData) {
            try {
                $admin = Admin::firstOrCreate(
                    ['email' => $adminData['email']],
                    $adminData
                );

                if ($admin->wasRecentlyCreated) {
                    $this->command->info("✅ Admin créé : {$adminData['prenom']} {$adminData['nom']} ({$adminData['email']})");
                } else {
                    $this->command->info("ℹ️ Admin déjà existant : {$adminData['prenom']} {$adminData['nom']} ({$adminData['email']})");
                }
            } catch (\Exception $e) {
                $this->command->error("❌ Erreur création admin {$adminData['email']} : {$e->getMessage()}");
            }
        }
    }

    /**
     * Créer des admins pour l'environnement de développement
     * Utilise Faker si disponible, sinon données statiques
     */
    private function createDevelopmentAdmins(): void
    {
        $this->command->info('🏠 Environnement de développement détecté - Création d\'admins...');

        // Créer des admins spécifiques pour les tests (éviter les doublons)
        $testAdmins = [
            [
                'nom' => 'Admin',
                'prenom' => 'Super',
                'email' => 'admin@banque.com',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ],
            [
                'nom' => 'Manager',
                'prenom' => 'Banque',
                'email' => 'manager@banque.com',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ],
        ];

        foreach ($testAdmins as $adminData) {
            try {
                $admin = Admin::firstOrCreate(
                    ['email' => $adminData['email']],
                    $adminData
                );

                if ($admin->wasRecentlyCreated) {
                    $this->command->info("✅ Admin de test créé : {$adminData['prenom']} {$adminData['nom']} ({$adminData['email']})");
                } else {
                    $this->command->info("ℹ️ Admin de test déjà existant : {$adminData['prenom']} {$adminData['nom']} ({$adminData['email']})");
                }
            } catch (\Exception $e) {
                $this->command->error("❌ Erreur création admin de test {$adminData['email']} : {$e->getMessage()}");
            }
        }

        // Créer des admins supplémentaires (seulement s'il n'y en a pas assez)
        $existingCount = Admin::count();
        $targetCount = 5; // Nombre total souhaité

        if ($existingCount < $targetCount) {
            $additionalAdminsCount = $targetCount - $existingCount;
            $this->command->info("🎲 Création de {$additionalAdminsCount} admins supplémentaires...");

            // Vérifier si Faker est disponible
            if ($this->isFakerAvailable()) {
                try {
                    // Utiliser Faker en développement
                    Admin::factory($additionalAdminsCount)->create();

                    foreach (Admin::latest()->take($additionalAdminsCount)->get() as $admin) {
                        $this->command->info("✅ Admin Faker créé : {$admin->prenom} {$admin->nom} ({$admin->email})");
                    }
                } catch (\Exception $e) {
                    $this->command->warn("⚠️ Faker indisponible, création d'admins statiques : {$e->getMessage()}");
                    $this->createStaticAdmins($additionalAdminsCount);
                }
            } else {
                $this->command->info("ℹ️ Faker non disponible, création d'admins statiques...");
                $this->createStaticAdmins($additionalAdminsCount);
            }
        } else {
            $this->command->info("ℹ️ Nombre d'admins suffisant ({$existingCount}), pas de création supplémentaire.");
        }

        $finalCount = Admin::count();
        $this->command->info("🎉 {$finalCount} admins présents en base de données.");
        $this->command->info('📝 Mot de passe par défaut pour tous les admins : password123');
        $this->command->warn('⚠️ Attention : Ne pas utiliser ces comptes en production !');
    }

    /**
     * Créer des admins statiques (sans Faker)
     */
    private function createStaticAdmins(int $count): void
    {
        for ($i = 1; $i <= $count; $i++) {
            try {
                $admin = Admin::firstOrCreate(
                    ['email' => "admin{$i}@banque.com"],
                    [
                        'nom' => "Admin{$i}",
                        'prenom' => "Test{$i}",
                        'password' => Hash::make('password123'),
                        'email_verified_at' => now(),
                    ]
                );

                if ($admin->wasRecentlyCreated) {
                    $this->command->info("✅ Admin statique créé : Test{$i} Admin{$i} (admin{$i}@banque.com)");
                } else {
                    $this->command->info("ℹ️ Admin statique déjà existant : Test{$i} Admin{$i} (admin{$i}@banque.com)");
                }
            } catch (\Exception $e) {
                $this->command->error("❌ Erreur création admin statique {$i} : {$e->getMessage()}");
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

    /**
     * Compter le nombre d'admins créés pendant ce seeding
     */
    private function countCreatedAdmins(): int
    {
        return Admin::count();
    }
}
