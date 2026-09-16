<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Creer la table casques.
     */
    public function up(): void
    {
        Schema::create('casques', function (Blueprint $table) {
            $table->string('numero_serie')->primary(); // ex: SN-WH1000XM5-001
            $table->foreignId('materiel_id')->unique()->constrained('materiels')->cascadeOnDelete();
            $table->string('connexion');
            $table->boolean('micro')->default(true);
            $table->boolean('reduction_bruit')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Supprimer la table casques.
     */
    public function down(): void
    {
        Schema::dropIfExists('casques');
    }
};
