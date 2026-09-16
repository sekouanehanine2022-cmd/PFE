<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Créer la table affectations
     */
    public function up(): void
    {
        Schema::create('affectations', function (Blueprint $table) {

            $table->id();
            $table->foreignId('personnel_id')              // qui reçoit le matériel
                  ->constrained('personnels')
                  ->onDelete('cascade');
            $table->foreignId('ticket_id')                 // ticket optionnel
                  ->nullable()
                  ->constrained('tickets')
                  ->onDelete('cascade');
            $table->foreignId('materiel_id')               // materiel affecte
                  ->constrained('materiels')
                  ->onDelete('cascade');
            $table->date('date_debut');                    // début affectation
            $table->date('date_fin')->nullable();          // fin affectation
            $table->enum('statut', [
                'active',
                'rendu'
            ])->default('active');
            $table->text('notes')->nullable();             // notes optionnelles
            $table->timestamps();

        });
    }

    /**
     * Supprimer la table affectations
     */
    public function down(): void
    {
        Schema::dropIfExists('affectations');
    }
};
