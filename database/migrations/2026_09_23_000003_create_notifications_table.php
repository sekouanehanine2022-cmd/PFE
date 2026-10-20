<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Cree ou adapte la structure de base de donnees definie par cette migration.
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->foreignId('ticket_id')
                ->nullable()
                ->constrained('tickets')
                ->cascadeOnDelete();
            $table->foreignId('affectation_id')
                ->nullable()
                ->constrained('affectations')
                ->cascadeOnDelete();
            $table->foreignId('emprunt_id')
                ->nullable()
                ->constrained('emprunts')
                ->cascadeOnDelete();
            $table->foreignId('cable_id')
                ->nullable()
                ->constrained('cables')
                ->cascadeOnDelete();
            $table->string('type');
            $table->string('message');
            $table->timestamp('read_at')->nullable();
            $table->date('date_echeance')->nullable();
            $table->timestamp('email_envoye_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'ticket_id', 'type']);
            $table->unique(['user_id', 'affectation_id', 'type', 'date_echeance'], 'notifications_affectation_echeance_unique');
            $table->unique(['user_id', 'emprunt_id', 'type', 'date_echeance'], 'notifications_emprunt_echeance_unique');
            $table->unique(['user_id', 'cable_id', 'type']);
            $table->index(['user_id', 'read_at']);
        });
    }

    // Annule les changements de structure de cette migration.
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
