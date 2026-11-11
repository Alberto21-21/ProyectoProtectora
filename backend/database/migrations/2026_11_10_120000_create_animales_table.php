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
        if (!Schema::hasTable('animales')) {
            Schema::create('animales', function (Blueprint $table) {
                $table->id();
                $table->string('nombre');
                $table->string('fotografia')->nullable();
                $table->string('imagen')->nullable();
                $table->enum('especie', ['Perro', 'Gato', 'Otro']);
                $table->string('raza')->nullable();
                $table->date('fecha_nacimiento')->nullable();
                $table->integer('edad')->nullable();
                $table->enum('sexo', ['Macho', 'Hembra']);
                $table->enum('tamano', ['Pequeño', 'Mediano', 'Grande'])->nullable();
                $table->decimal('peso', 5, 2)->nullable();
                $table->date('fecha_entrada')->nullable();
                $table->string('motivo')->nullable();

                // ESTADOS PARA CONTROLAR LA SITUACIÓN DEL ANIMAL EN EL CENTRO
                $table->enum('estado', ['En acogida', 'En tratamiento', 'Disponible', 'Reservado', 'Adoptado'])
                    ->default('En acogida');

                $table->text('caracter')->nullable();
                $table->text('descripcion')->nullable();
                $table->enum('con_ninos', ['Compatible', 'No compatible', 'Desconocido'])->default('Desconocido');
                $table->enum('con_otros_animales', ['Compatible', 'No compatible', 'Desconocido'])->default('Desconocido');
                $table->enum('con_desconocidos', ['Compatible', 'No compatible', 'Desconocido'])->default('Desconocido');
                $table->text('necesidades_especiales')->nullable();
                $table->text('observaciones')->nullable();
                $table->text('observaciones_clinicas')->nullable();

                // USUARIO DEL PERSONAL QUE DA DE ALTA AL ANIMAL
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('animales');
    }
};