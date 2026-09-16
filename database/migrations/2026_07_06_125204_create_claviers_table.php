<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Creer la table claviers.
     */
    public function up(): void
    {
        Schema::create('claviers', function (Blueprint $table) {
            $table->string('numero_serie')->primary(); // ex: LG-MXKEYS-001
            $table->foreignId('materiel_id')->unique()->constrained('materiels')->cascadeOnDelete();
            $table->string('connexion');
            $table->string('disposition')->nullable(); // ex: AZERTY
            $table->boolean('retro_eclairage')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Supprimer la table claviers.
     */
    public function down(): void
    {
        Schema::dropIfExists('claviers');
    }
};
