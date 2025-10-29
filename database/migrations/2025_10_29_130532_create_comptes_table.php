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
        Schema::create('comptes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('numero_compte', 20)->unique();
            $table->enum('type', ['cheque', 'epargne', 'courant'])->default('cheque');
            $table->decimal('solde_initial', 15, 2)->default(0);
            $table->string('devise', 10)->default('FCFA');
            $table->enum('statut', ['actif', 'inactif', 'bloque', 'ferme'])->default('actif');
            $table->foreignUuid('client_id')->constrained('clients')->onDelete('cascade');
            $table->timestamps();

            // Indexes pour les performances
            $table->index('numero_compte');
            $table->index('type');
            $table->index('statut');
            $table->index('client_id');
            $table->index(['client_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comptes');
    }
};
