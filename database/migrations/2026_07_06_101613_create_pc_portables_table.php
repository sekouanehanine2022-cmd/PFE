<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Créer la table pc_portables
     */
    public function up(): void
    {
        Schema::create('pc_portables', function (Blueprint $table) {

            $table->id();
            $table->string('reference')->unique();   // ex: PP-001
            $table->string('nom');                   // ex: Dell Latitude 5540
            $table->string('marque');                // ex: Dell
            $table->string('numero_serie')->unique();// ex: DL2023-5540-001
            $table->string('adresse_mac')->nullable();
            $table->string('cpu');                   // ex: Intel i5-1335U
            $table->string('ram');                   // ex: 16 Go DDR5
            $table->string('stockage');              // ex: 512 Go SSD
            $table->string('os');                    // ex: Windows 11
            $table->string('ecran')->nullable();     // ex: 15.6 pouces
            $table->enum('etat', [
    'disponible',
    'affecte',
    'emprunte',
    'en_panne'
])->default('disponible');
            $table->string('emplacement')->nullable(); // ex: Salle 101
            $table->date('date_achat')->nullable();
            $table->timestamps();

        });
    }

    /**
     * Supprimer la table pc_portables
     */
    public function down(): void
    {
        Schema::dropIfExists('pc_portables');
    }
};