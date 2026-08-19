<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Créer la table tickets
     */
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {

            $table->id();
            $table->string('titre');                        // ex: PC portable ne démarre plus
            $table->text('description')->nullable();        // détail du problème
            $table->enum('type', [
                'incident',                                 // panne, problème
                'affectation',                              // demande d'affectation
                'emprunt'                                   // demande d'emprunt
            ]);
            $table->enum('priorite', [
                'haute',
                'normale',
                'basse'
            ])->default('normale');
            $table->enum('statut', [
                'ouvert',
                'en_cours',
                'resolu',
                'ferme'
            ])->default('ouvert');
            $table->foreignId('demandeur_id')              // qui a créé le ticket
                  ->constrained('users')
                  ->onDelete('cascade');
            $table->foreignId('technicien_id')             // qui traite le ticket
                  ->nullable()
                  ->constrained('users')
                  ->onDelete('set null');
            $table->string('materiel_type')->nullable();   // ex: App\Models\PcPortable
            $table->unsignedBigInteger('materiel_id')->nullable(); // id du matériel
            $table->timestamps();

        });
    }

    /**
     * Supprimer la table tickets
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};