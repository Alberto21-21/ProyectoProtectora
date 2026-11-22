<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mensajes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('conversacion_id')
                ->constrained('conversaciones')
                ->cascadeOnDelete();

            // Emisor del mensaje
            $table->foreignId('user_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->text('texto');

            // created_at guarda la fecha y hora del mensaje
            $table->timestamps();

            $table->index(['conversacion_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mensajes');
    }
};

