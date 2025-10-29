<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TransactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Créer des transactions pour les comptes existants
        $comptes = \App\Models\Compte::all();

        foreach ($comptes as $compte) {
            // Créer 3-8 transactions par compte
            $nombreTransactions = rand(3, 8);

            for ($i = 0; $i < $nombreTransactions; $i++) {
                $type = collect(['depot', 'retrait', 'virement', 'transfert', 'frais'])->random();

                // Créer la transaction selon le type
                $factory = \App\Models\Transaction::factory()->forCompte($compte);

                switch ($type) {
                    case 'depot':
                        $factory->depot();
                        break;
                    case 'retrait':
                        $factory->retrait();
                        break;
                    case 'virement':
                    case 'transfert':
                        $factory->virement();
                        break;
                    case 'frais':
                        $factory->state(['type' => 'frais']);
                        break;
                }

                $factory->create();
            }
        }

        // Créer quelques transactions spécifiques
        $compteHawa = \App\Models\Compte::whereHas('client', function($query) {
            $query->where('email', 'cheikh.sy@example.com');
        })->first();

        if ($compteHawa) {
            // Dépôt initial
            \App\Models\Transaction::factory()->forCompte($compteHawa)->create([
                'type' => 'depot',
                'montant' => 500000,
                'description' => 'Dépôt initial',
                'statut' => 'validee',
            ]);

            // Quelques retraits
            \App\Models\Transaction::factory()->forCompte($compteHawa)->create([
                'type' => 'retrait',
                'montant' => 250000,
                'description' => 'Retrait DAB',
                'statut' => 'validee',
            ]);

            \App\Models\Transaction::factory()->forCompte($compteHawa)->create([
                'type' => 'retrait',
                'montant' => 150000,
                'description' => 'Paiement facture',
                'statut' => 'validee',
            ]);
        }
    }
}
