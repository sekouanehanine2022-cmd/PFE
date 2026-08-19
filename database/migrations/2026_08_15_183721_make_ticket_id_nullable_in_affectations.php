<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('affectations', function (Blueprint $table) {
            $table->foreignId('ticket_id')->nullable()->change();
        });

        Schema::table('emprunts', function (Blueprint $table) {
            $table->foreignId('ticket_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('affectations', function (Blueprint $table) {
            $table->foreignId('ticket_id')->nullable(false)->change();
        });

        Schema::table('emprunts', function (Blueprint $table) {
            $table->foreignId('ticket_id')->nullable(false)->change();
        });
    }
};