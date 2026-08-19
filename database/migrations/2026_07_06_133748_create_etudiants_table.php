<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Créer la table etudiants
     */
    public function up(): void
    {
        Schema::create('etudiants', function (Blueprint $table) {

            $table->id();
            $table->foreignId('user_id')                    // lien vers users
                  ->constrained()
                  ->onDelete('cascade');
            $table->enum('type', [
                'alt_externe',                              // Alternant Externe
                'alt_interne',                              // Alternant Interne
                'etud_initial'                              // Étudiant Initial
            ]);
            $table->string('promotion')->nullable();        // ex: BTS SIO 2024
            $table->date('date_fin_formation')->nullable(); // ex: 30/06/2024
            $table->string('etablissement')->nullable();    // ex: EFEL Paris
            $table->timestamps();

        });
    }

    /**
     * Supprimer la table etudiants
     */
    public function down(): void
    {
        Schema::dropIfExists('etudiants');
    }
};