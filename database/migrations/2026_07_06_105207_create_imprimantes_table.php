<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Créer la table imprimantes
     */
    public function up(): void
    {
        Schema::create('imprimantes', function (Blueprint $table) {

            $table->id();
            $table->string('reference')->unique();    // ex: IM-001
            $table->string('nom');                    // ex: Canon MF445dw
            $table->string('marque');                 // ex: Canon
            $table->string('numero_serie')->unique(); // ex: CN2022-MF445-001
            $table->string('type_impression');        // ex: Laser
            $table->boolean('couleur')->default(false); // true = couleur
            $table->string('connexion');              // ex: Wi-Fi
            $table->string('vitesse')->nullable();    // ex: 38 ppm
            $table->enum('etat', [
                'disponible',
                'en_panne',
            ])->default('disponible');
            $table->string('emplacement')->nullable();
            $table->date('date_achat')->nullable();
            $table->timestamps();

        });
    }

    /**
     * Supprimer la table imprimantes
     */
    public function down(): void
    {
        Schema::dropIfExists('imprimantes');
    }
};