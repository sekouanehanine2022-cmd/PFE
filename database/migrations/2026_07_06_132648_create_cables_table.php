<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Créer la table cables
     */
    public function up(): void
    {
        Schema::create('cables', function (Blueprint $table) {

            $table->id();
            $table->string('reference')->unique();    // ex: CB-001
            $table->string('type_cable');             // ex: HDMI 2.0
            $table->string('longueur');               // ex: 1.5m
            $table->integer('quantite')->default(0);  // stock total
            $table->integer('quantite_disponible')->default(0); // disponibles
            $table->integer('seuil_alerte')->default(5); // alerte stock bas
            $table->string('couleur')->nullable();    // ex: Noir
            $table->enum('etat', [
                'bon_etat',
                'etat_moyen',
                'hors_service'
            ])->default('bon_etat');
            $table->string('emplacement')->nullable();
            $table->timestamps();

        });
    }

    /**
     * Supprimer la table cables
     */
    public function down(): void
    {
        Schema::dropIfExists('cables');
    }
};