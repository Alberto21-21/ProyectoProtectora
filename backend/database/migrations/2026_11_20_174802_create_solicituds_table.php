<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('solicitudes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->foreignId('animal_id')
                ->constrained('animales')
                ->restrictOnDelete();

            $table->string('estado', 30)->default('pendiente');
            $table->text('observaciones')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'animal_id', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('solicitudes');
    }
};