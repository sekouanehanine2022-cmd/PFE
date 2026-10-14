<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Créer la table ecrans
     */
    public function up(): void
    {
        Schema::create('ecrans', function (Blueprint $table) {

            $table->foreignId('materiel_id')->primary()->constrained('materiels')->cascadeOnDelete();
            $table->string('numero_serie')->unique();// ex: LG2022-27UK-001
            $table->string('taille');                 // ex: 27 pouces
            $table->string('resolution');             // ex: 4K UHD
            $table->string('dalle')->nullable();      // ex: IPS
            $table->string('taux_rafraichissement')->nullable(); // ex: 60 Hz
            $table->timestamps();

        });
    }

    /**
     * Supprimer la table ecrans
     */
    public function down(): void
    {
        Schema::dropIfExists('ecrans');
    }
};
