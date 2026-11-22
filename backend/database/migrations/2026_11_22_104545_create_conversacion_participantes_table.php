<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // El intento fallido anterior dejó esta tabla vacía y sin claves
        Schema::dropIfExists('conversacion_participantes');

        Schema::create('conversacion_participantes', function (Blueprint $table) {
            $table->foreignId('conversacion_id')->constrained('conversaciones')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->primary(['conversacion_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversacion_participantes');
    }
};