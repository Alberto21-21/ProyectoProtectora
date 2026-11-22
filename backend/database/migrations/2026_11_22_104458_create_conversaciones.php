<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversaciones', function (Blueprint $table) {
            $table->id();
            $table->string('tipo', 20)->default('conversacion');
            $table->string('asunto', 120)->nullable();
            $table->foreignId('animal_id')->nullable()->constrained('animales')->nullOnDelete();
            $table->foreignId('creada_por_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('ultimo_mensaje_at')->nullable();
            $table->timestamps();
            $table->index('ultimo_mensaje_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversaciones');
    }
};
