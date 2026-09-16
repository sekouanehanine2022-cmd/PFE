<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Creer la table centrale des materiels.
     */
    public function up(): void
    {
        Schema::create('materiels', function (Blueprint $table) {
            $table->id();
            $table->enum('type_materiel', [
                'pc_portable',
                'mini_pc',
                'ecran',
                'imprimante',
                'clavier',
                'souris',
                'casque',
            ]);
            $table->string('nom');
            $table->string('marque');
            $table->enum('etat', [
                'disponible',
                'affecte',
                'emprunte',
                'en_panne',
            ])->default('disponible');
            $table->string('emplacement')->nullable();
            $table->date('date_achat')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Supprimer la table centrale des materiels.
     */
    public function down(): void
    {
        Schema::dropIfExists('materiels');
    }
};
