<?php

namespace App\Jobs;

use App\Models\Compte;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ArchiveBlockedComptes implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * Archive automatiquement les comptes bloqués dont la date de début de blocage est atteinte
     */
    public function handle(): void
    {
        Log::info('Démarrage du job d\'archivage automatique des comptes bloqués');

        // Trouver tous les comptes épargne bloqués dont la date de début de blocage est atteinte
        $comptesToArchive = Compte::where('statut', 'bloque')
            ->where('type', 'epargne')
            ->whereNotNull('date_debut_blocage')
            ->where('date_debut_blocage', '<=', now())
            ->whereNull('archived_at')
            ->get();

        $archivedCount = 0;

        foreach ($comptesToArchive as $compte) {
            try {
                // Étape 1: Sauvegarder le client dans Neon s'il n'existe pas déjà
                $this->saveClientToNeon($compte->client);

                // Étape 2: Sauvegarder le compte dans Neon
                $this->saveCompteToNeon($compte);

                // Étape 3: Marquer le compte comme archivé dans la base principale
                $compte->update([
                    'archived_at' => now(),
                    'statut' => 'archive' // Changer le statut à archivé
                ]);

                Log::info("Compte {$compte->numero_compte} archivé automatiquement dans Neon", [
                    'compte_id' => $compte->id,
                    'date_debut_blocage' => $compte->date_debut_blocage,
                    'motif_blocage' => $compte->motif_blocage
                ]);

                $archivedCount++;
            } catch (\Exception $e) {
                Log::error("Erreur lors de l'archivage du compte {$compte->numero_compte}", [
                    'compte_id' => $compte->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }

        Log::info("Job d'archivage terminé : {$archivedCount} comptes archivés");
    }

    /**
     * Sauvegarde le client dans la base Neon s'il n'existe pas déjà
     */
    private function saveClientToNeon($client)
    {
        try {
            // Vérifier si le client existe déjà dans Neon
            $existingClient = DB::connection('neon')
                ->table('clients')
                ->where('id', $client->id)
                ->first();

            if (!$existingClient) {
                DB::connection('neon')->table('clients')->insert([
                    'id' => $client->id,
                    'titulaire' => $client->titulaire,
                    'nci' => $client->nci,
                    'email' => $client->email,
                    'telephone' => $client->telephone,
                    'adresse' => $client->adresse,
                    'statut' => $client->statut,
                    'email_verified_at' => $client->email_verified_at,
                    'password' => $client->password,
                    'code' => $client->code,
                    'created_at' => $client->created_at,
                    'updated_at' => $client->updated_at,
                ]);

                Log::info("Client {$client->id} sauvegardé dans Neon", [
                    'client_id' => $client->id,
                    'email' => $client->email
                ]);
            }
        } catch (\Exception $e) {
            Log::error("Erreur lors de la sauvegarde du client dans Neon", [
                'client_id' => $client->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Sauvegarde le compte dans la base Neon
     */
    private function saveCompteToNeon($compte)
    {
        try {
            DB::connection('neon')->table('comptes')->insert([
                'id' => $compte->id,
                'numero_compte' => $compte->numero_compte,
                'type' => $compte->type,
                'solde_initial' => $compte->solde_initial,
                'devise' => $compte->devise,
                'statut' => $compte->statut,
                'client_id' => $compte->client_id,
                'motif_blocage' => $compte->motif_blocage,
                'date_debut_blocage' => $compte->date_debut_blocage,
                'date_fin_blocage' => $compte->date_fin_blocage,
                'date_deblocage' => $compte->date_deblocage,
                'motif_deblocage' => $compte->motif_deblocage,
                'archived_at' => now(),
                'deleted_at' => $compte->deleted_at,
                'created_at' => $compte->created_at,
                'updated_at' => $compte->updated_at,
            ]);

            Log::info("Compte {$compte->numero_compte} sauvegardé dans Neon", [
                'compte_id' => $compte->id,
                'numero_compte' => $compte->numero_compte
            ]);
        } catch (\Exception $e) {
            Log::error("Erreur lors de la sauvegarde du compte dans Neon", [
                'compte_id' => $compte->id,
                'numero_compte' => $compte->numero_compte,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
