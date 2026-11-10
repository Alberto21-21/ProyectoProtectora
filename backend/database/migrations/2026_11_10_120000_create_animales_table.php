<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // SOLO SE CREA SI LA TABLA NO EXISTE TODAVÍA
        if (!Schema::hasTable('animales')) {
            Schema::create('animales', function (Blueprint $table) {
                $table->id();
                $table->string('nombre');
                $table->string('especie', 100);
                $table->string('raza', 100)->nullable();
                $table->integer('edad')->nullable();
                $table->string('sexo');
                $table->string('estado')->default('Disponible');
                $table->text('descripcion')->nullable();
                $table->string('imagen')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('animales');
    }
};