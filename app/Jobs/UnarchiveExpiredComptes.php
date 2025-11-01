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

        // Trouver tous les comptes épargne archivés dans Neon dont la date de fin de blocage est dépassée
        $comptesToUnarchive = DB::connection('neon')
            ->table('comptes')
            ->where('statut', 'archive')
            ->where('type', 'epargne')
            ->whereNotNull('date_fin_blocage')
            ->where('date_fin_blocage', '<=', now())
            ->whereNotNull('archived_at')
            ->get();

        $unarchivedCount = 0;

        foreach ($comptesToUnarchive as $compteData) {
            try {
                // Récupérer le compte dans la base principale
                $compte = Compte::withTrashed()->find($compteData->id);

                if ($compte) {
                    // Restaurer le compte depuis Neon
                    $compte->update([
                        'statut' => 'actif',
                        'archived_at' => null,
                        'date_debut_blocage' => null,
                        'date_fin_blocage' => null,
                        'motif_blocage' => null,
                        'date_deblocage' => now(),
                        'motif_deblocage' => 'Période de blocage expirée - Restauré depuis Neon',
                        'deleted_at' => null // Restaurer le soft delete
                    ]);

                    Log::info("Compte {$compte->numero_compte} désarchivé automatiquement depuis Neon", [
                        'compte_id' => $compte->id,
                        'date_fin_blocage' => $compteData->date_fin_blocage,
                        'motif_deblocage' => 'Période de blocage expirée'
                    ]);
                } else {
                    Log::warning("Compte {$compteData->numero_compte} trouvé dans Neon mais pas dans la base principale", [
                        'compte_id' => $compteData->id
                    ]);
                }

                $unarchivedCount++;
            } catch (\Exception $e) {
                Log::error("Erreur lors du désarchivage du compte {$compteData->numero_compte}", [
                    'compte_id' => $compteData->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        Log::info("Job de désarchivage terminé : {$unarchivedCount} comptes désarchivés depuis Neon");
    }
}
