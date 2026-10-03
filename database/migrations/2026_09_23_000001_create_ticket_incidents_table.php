<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_incidents', function (Blueprint $table) {
            $table->foreignId('ticket_id')
                ->primary()
                ->constrained('tickets')
                ->cascadeOnDelete();
            $table->text('reponse_admin')->nullable();
            $table->timestamp('date_reponse')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_incidents');
    }
};
