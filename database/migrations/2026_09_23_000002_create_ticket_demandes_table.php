<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Cree ou adapte la structure de base de donnees definie par cette migration.
    public function up(): void
    {
        Schema::create('ticket_demandes', function (Blueprint $table) {
            $table->foreignId('ticket_id')
                ->primary()
                ->constrained('tickets')
                ->cascadeOnDelete();
            $table->enum('type_demande', ['affectation', 'emprunt']);
            $table->text('motif_refus')->nullable();
            $table->timestamp('date_refus')->nullable();
        });
    }

    // Annule les changements de structure de cette migration.
    public function down(): void
    {
        Schema::dropIfExists('ticket_demandes');
    }
};
