<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Creer la table souris.
     */
    public function up(): void
    {
        Schema::create('souris', function (Blueprint $table) {
            $table->foreignId('materiel_id')->primary()->constrained('materiels')->cascadeOnDelete();
            $table->string('numero_serie')->unique(); // ex: LG-MX3-001
            $table->string('connexion');
            $table->unsignedSmallInteger('dpi')->nullable(); // ex: 1600
            $table->unsignedTinyInteger('nombre_boutons')->nullable(); // ex: 5
            $table->timestamps();
        });
    }

    /**
     * Supprimer la table souris.
     */
    public function down(): void
    {
        Schema::dropIfExists('souris');
    }
};
