<?php

namespace App\Jobs;

use App\Models\Compte;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
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

        // Trouver tous les comptes bloqués dont la date de début de blocage est atteinte
        $comptesToArchive = Compte::where('statut', 'bloque')
            ->whereNotNull('date_debut_blocage')
            ->where('date_debut_blocage', '<=', now())
            ->whereNull('archived_at')
            ->get();

        $archivedCount = 0;

        foreach ($comptesToArchive as $compte) {
            try {
                // Archiver le compte en définissant archived_at
                $compte->update([
                    'archived_at' => now(),
                    'statut' => 'archive' // Changer le statut à archivé
                ]);

                Log::info("Compte {$compte->numero_compte} archivé automatiquement", [
                    'compte_id' => $compte->id,
                    'date_debut_blocage' => $compte->date_debut_blocage,
                    'motif_blocage' => $compte->motif_blocage
                ]);

                $archivedCount++;
            } catch (\Exception $e) {
                Log::error("Erreur lors de l'archivage du compte {$compte->numero_compte}", [
                    'compte_id' => $compte->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        Log::info("Job d'archivage terminé : {$archivedCount} comptes archivés");
    }
}
