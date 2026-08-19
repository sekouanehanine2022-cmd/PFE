<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Créer la table personnels
     */
    public function up(): void
    {
        Schema::create('personnels', function (Blueprint $table) {

            $table->id();
            $table->foreignId('user_id')             // lien vers users
                  ->constrained()
                  ->onDelete('cascade');
            $table->string('service')->nullable();    // ex: Pédagogie
            $table->string('poste')->nullable();      // ex: Technicien
            $table->string('telephone')->nullable();  // ex: 06 12 34 56 78
            $table->enum('role', [
                'admin',
                'technicien',
                'responsable'
            ])->default('technicien');
            $table->timestamps();

        });
    }

    /**
     * Supprimer la table personnels
     */
    public function down(): void
    {
        Schema::dropIfExists('personnels');
    }
};