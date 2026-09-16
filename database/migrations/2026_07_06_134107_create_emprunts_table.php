<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Créer la table emprunts
     */
    public function up(): void
    {
        Schema::create('emprunts', function (Blueprint $table) {

            $table->id();
            $table->foreignId('etudiant_id')               // qui emprunte
                  ->constrained('etudiants')
                  ->onDelete('cascade');
            $table->foreignId('ticket_id')                 // ticket optionnel
                  ->nullable()
                  ->constrained('tickets')
                  ->onDelete('cascade');
            $table->foreignId('materiel_id')               // materiel emprunte
                  ->constrained('materiels')
                  ->onDelete('cascade');
            $table->date('date_debut');                    // début emprunt
            $table->date('date_fin_prevue');               // fin prévue
            $table->date('date_retour')->nullable();       // date retour réel
            $table->enum('statut', [
                'en_cours',
                'echeance_proche',
                'rendu',
                'en_retard'
            ])->default('en_cours');
            $table->text('notes')->nullable();
            $table->timestamps();

        });
    }

    /**
     * Supprimer la table emprunts
     */
    public function down(): void
    {
        Schema::dropIfExists('emprunts');
    }
};
