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
        Schema::create('transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('numero_transaction', 20)->unique();
            $table->enum('type', ['depot', 'retrait', 'virement', 'transfert', 'frais'])->default('depot');
            $table->decimal('montant', 15, 2);
            $table->string('devise', 10)->default('FCFA');
            $table->text('description')->nullable();
            $table->enum('statut', ['en_attente', 'validee', 'rejete', 'annulee'])->default('validee');
            $table->timestamp('date_transaction');
            $table->foreignUuid('compte_id')->constrained('comptes')->onDelete('cascade');
            $table->foreignUuid('compte_destination_id')->nullable()->constrained('comptes')->onDelete('set null');
            $table->timestamps();

            // Indexes pour les performances
            $table->index('numero_transaction');
            $table->index('type');
            $table->index('statut');
            $table->index('date_transaction');
            $table->index('compte_id');
            $table->index('compte_destination_id');
            $table->index(['compte_id', 'date_transaction']);
            $table->index(['type', 'statut']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
