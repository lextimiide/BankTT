<?php

namespace App\Console\Commands;

use App\Models\Admin;
use App\Models\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Hash;

class SeedProductionData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:seed-production-data {--force : Forcer le seeding même en production}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seeder les données essentielles pour la production (Admins et Clients de test)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Vérification de sécurité pour éviter les accidents en production
        if (App::environment('production') && !$this->option('force')) {
            $this->error('❌ ATTENTION: Vous êtes en environnement de production!');
            $this->warn('Cette commande va créer des données de test dans la base de production.');
            $this->warn('Utilisez --force pour confirmer que vous voulez continuer.');

            if (!$this->confirm('Voulez-vous vraiment continuer ?')) {
                $this->info('Opération annulée.');
                return;
            }
        }

        $this->info('🚀 Début du seeding des données de production...');
        $this->newLine();

        // Seeder les admins
        $this->seedAdmins();

        // Seeder les clients
        $this->seedClients();

        $this->newLine();
        $this->info('✅ Seeding terminé avec succès !');
        $this->info('📊 Résumé:');
        $this->info("   - Admins: " . Admin::count());
        $this->info("   - Clients: " . Client::count());
        $this->newLine();

        // Afficher les comptes de connexion
        $this->displayLoginCredentials();
    }

    /**
     * Seeder les administrateurs
     */
    private function seedAdmins(): void
    {
        $this->info('👤 Seeding des administrateurs...');

        $admins = [
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

        foreach ($admins as $adminData) {
            Admin::firstOrCreate(
                ['email' => $adminData['email']],
                $adminData
            );
            $this->line("   ✅ Admin créé: {$adminData['prenom']} {$adminData['nom']} ({$adminData['email']})");
        }
    }

    /**
     * Seeder les clients
     */
    private function seedClients(): void
    {
        $this->info('👥 Seeding des clients...');

        $clients = [
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
        ];

        foreach ($clients as $clientData) {
            Client::firstOrCreate(
                ['email' => $clientData['email']],
                $clientData
            );
            $this->line("   ✅ Client créé: {$clientData['titulaire']} ({$clientData['email']})");
        }
    }

    /**
     * Afficher les informations de connexion
     */
    private function displayLoginCredentials(): void
    {
        $this->info('🔐 Informations de connexion pour les tests:');
        $this->newLine();

        $this->table(
            ['Rôle', 'Email', 'Mot de passe'],
            [
                ['Admin', 'admin@banque.com', 'password123'],
                ['Admin', 'manager@banque.com', 'password123'],
                ['Client', 'hawa.wane@example.com', 'password123'],
                ['Client', 'mamadou.diallo@example.com', 'password123'],
            ]
        );

        $this->newLine();
        $this->warn('⚠️  Ces comptes sont uniquement pour les tests!');
        $this->warn('   Supprimez-les ou changez les mots de passe en production.');
    }
}
