<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Créer la table peripheriques
     * (Claviers, Souris, Casques)
     */
    public function up(): void
    {
        Schema::create('peripheriques', function (Blueprint $table) {

            $table->id();
            $table->string('reference')->unique();    // ex: CL-001
            $table->string('nom');                    // ex: Logitech MX Keys
            $table->string('marque');                 // ex: Logitech
            $table->string('numero_serie')->unique(); // ex: LG-MXKEYS-001
            $table->enum('sous_type', [
                'clavier',
                'souris',
                'casque'
            ]);
            $table->enum('connexion', [
                'bluetooth',
                'filaire',
                'sans_fil'
            ]);
            $table->string('disposition')->nullable(); // ex: AZERTY
            $table->boolean('retro_eclairage')->default(false);
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
     * Supprimer la table peripheriques
     */
    public function down(): void
    {
        Schema::dropIfExists('peripheriques');
    }
};