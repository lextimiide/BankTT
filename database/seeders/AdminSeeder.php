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
     */
    public function run(): void
    {
        // En environnement de production, créer seulement des admins par défaut
        if (App::environment('production')) {
            $this->createProductionAdmins();
        } else {
            // En développement/local, utiliser Faker pour créer des admins aléatoires
            $this->createDevelopmentAdmins();
        }
    }

    /**
     * Créer des admins pour l'environnement de production
     * Utilise firstOrCreate pour éviter les doublons
     */
    private function createProductionAdmins(): void
    {
        $this->command->info('🌍 Environnement de production détecté - Création d\'admins par défaut...');

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
            Admin::firstOrCreate(
                ['email' => $adminData['email']], // Condition de recherche
                $adminData // Données à créer si non trouvé
            );
            $this->command->info("✅ Admin créé/mis à jour : {$adminData['prenom']} {$adminData['nom']} ({$adminData['email']})");
        }

        $this->command->info("🎉 " . Admin::count() . " admins présents en base de données.");
    }

    /**
     * Créer des admins pour l'environnement de développement
     * Utilise Faker pour générer des données aléatoires
     */
    private function createDevelopmentAdmins(): void
    {
        $this->command->info('🏠 Environnement de développement détecté - Création d\'admins avec Faker...');

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
            Admin::firstOrCreate(
                ['email' => $adminData['email']], // Condition de recherche
                $adminData // Données à créer si non trouvé
            );
            $this->command->info("✅ Admin de test créé/mis à jour : {$adminData['prenom']} {$adminData['nom']} ({$adminData['email']})");
        }

        // Créer des admins supplémentaires avec Faker (seulement s'il n'y en a pas assez)
        $existingCount = Admin::count();
        $targetCount = 5; // Nombre total souhaité

        if ($existingCount < $targetCount) {
            $additionalAdminsCount = $targetCount - $existingCount;
            $this->command->info("🎲 Création de {$additionalAdminsCount} admins supplémentaires...");

            if (App::environment('production')) {
                // En production, créer des admins statiques
                for ($i = 1; $i <= $additionalAdminsCount; $i++) {
                    Admin::firstOrCreate(
                        ['email' => "admin{$i}@banque.com"],
                        [
                            'nom' => "Admin{$i}",
                            'prenom' => "Test{$i}",
                            'email' => "admin{$i}@banque.com",
                            'password' => Hash::make('password123'),
                            'email_verified_at' => now(),
                        ]
                    );
                    $this->command->info("✅ Admin créé : Test{$i} Admin{$i} (admin{$i}@banque.com)");
                }
            } else {
                // En développement, utiliser Faker
                Admin::factory($additionalAdminsCount)->create();

                foreach (Admin::latest()->take($additionalAdminsCount)->get() as $admin) {
                    $this->command->info("✅ Admin Faker créé : {$admin->prenom} {$admin->nom} ({$admin->email})");
                }
            }
        } else {
            $this->command->info("ℹ️  Nombre d'admins suffisant ({$existingCount}), pas de création supplémentaire.");
        }

        $finalCount = Admin::count();
        $this->command->info("🎉 {$finalCount} admins présents en base de données.");
        $this->command->info('📝 Mot de passe par défaut pour tous les admins : password123');
        $this->command->warn('⚠️  Attention : Ne pas utiliser ces comptes en production !');
    }

    /**
     * Compter le nombre d'admins créés pendant ce seeding
     */
    private function countCreatedAdmins(): int
    {
        return Admin::count();
    }
}
