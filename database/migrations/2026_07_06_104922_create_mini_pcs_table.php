<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Créer la table mini_pcs
     */
    public function up(): void
    {
        Schema::create('mini_pcs', function (Blueprint $table) {

            $table->id();
            $table->string('reference')->unique();    // ex: MP-001
            $table->string('nom');                    // ex: Dell OptiPlex 3000
            $table->string('marque');                 // ex: Dell
            $table->string('numero_serie')->unique(); // ex: DP2022-3000-001
            $table->string('cpu');                    // ex: Intel i3-1215U
            $table->string('ram');                    // ex: 8 Go DDR4
            $table->string('stockage');               // ex: 256 Go SSD
            $table->string('os');                     // ex: Windows 11
            $table->string('adresse_mac')->nullable();// ex: A1:B2:C3:D4:E5:F6
            $table->enum('etat', [
    'disponible',
    'affecte',
    'emprunte',
    'en_panne'
])->default('disponible');
            $table->string('emplacement')->nullable(); // ex: Bureau Admin
            $table->date('date_achat')->nullable();
            $table->timestamps();

        });
    }

    /**
     * Supprimer la table mini_pcs
     */
    public function down(): void
    {
        Schema::dropIfExists('mini_pcs');
    }
};