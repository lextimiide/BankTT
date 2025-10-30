<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('comptes', function (Blueprint $table) {
            $table->text('motif_blocage')->nullable()->after('statut');
            $table->timestamp('date_debut_blocage')->nullable()->after('motif_blocage');
            $table->timestamp('date_fin_blocage')->nullable()->after('date_debut_blocage');
            $table->timestamp('date_deblocage')->nullable()->after('date_fin_blocage');
            $table->text('motif_deblocage')->nullable()->after('date_deblocage');

            // Index pour les performances
            $table->index('date_fin_blocage');
            $table->index(['statut', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('comptes', function (Blueprint $table) {
            $table->dropIndex(['statut', 'type']);
            $table->dropIndex(['date_fin_blocage']);
            $table->dropColumn([
                'motif_deblocage',
                'date_deblocage',
                'date_fin_blocage',
                'date_debut_blocage',
                'motif_blocage'
            ]);
        });
    }
};
