<?php

namespace App\Jobs;

use App\Models\Compte;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class UnarchiveExpiredComptes implements ShouldQueue
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
     * Désarchive automatiquement les comptes dont la période de blocage est expirée
     */
    public function handle(): void
    {
        Log::info('Démarrage du job de désarchivage automatique des comptes expirés');

        // Trouver tous les comptes épargne archivés dont la date de fin de blocage est dépassée
        $comptesToUnarchive = Compte::where('statut', 'archive')
            ->where('type', 'epargne')
            ->whereNotNull('date_fin_blocage')
            ->where('date_fin_blocage', '<=', now())
            ->whereNotNull('archived_at')
            ->get();

        $unarchivedCount = 0;

        foreach ($comptesToUnarchive as $compte) {
            try {
                // Désarchiver le compte en remettant à actif et nettoyant archived_at
                $compte->update([
                    'statut' => 'actif',
                    'archived_at' => null,
                    'date_debut_blocage' => null,
                    'date_fin_blocage' => null,
                    'motif_blocage' => null,
                    'date_deblocage' => now()
                ]);

                Log::info("Compte {$compte->numero_compte} désarchivé automatiquement", [
                    'compte_id' => $compte->id,
                    'date_fin_blocage' => $compte->date_fin_blocage,
                    'motif_deblocage' => 'Période de blocage expirée'
                ]);

                $unarchivedCount++;
            } catch (\Exception $e) {
                Log::error("Erreur lors du désarchivage du compte {$compte->numero_compte}", [
                    'compte_id' => $compte->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        Log::info("Job de désarchivage terminé : {$unarchivedCount} comptes désarchivés");
    }
}
