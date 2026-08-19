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

            $table->id();
            $table->string('reference')->unique();    // ex: EC-001
            $table->string('nom');                    // ex: LG 27UK850-W
            $table->string('marque');                 // ex: LG
            $table->string('numero_serie')->unique(); // ex: LG2022-27UK-001
            $table->string('taille');                 // ex: 27 pouces
            $table->string('resolution');             // ex: 4K UHD
            $table->string('dalle')->nullable();      // ex: IPS
            $table->string('taux_rafraichissement')->nullable(); // ex: 60 Hz
            $table->enum('etat', [
                'disponible',
                'affecte',
                'emprunte',
                'en_panne',
                'maintenance',
                'hors_service'
            ])->default('disponible');
            $table->string('emplacement')->nullable();
            $table->date('date_achat')->nullable();
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