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
        // HISTORIAL CRONOLÓGICO DEL ANIMAL: CADA ACONTECIMIENTO ES UN REGISTRO INDEPENDIENTE (T18)
        Schema::create('historiales', function (Blueprint $table) {
            $table->id();

            // ANIMAL AL QUE PERTENECE EL REGISTRO (NO SE PUEDE BORRAR UN ANIMAL CON HISTORIAL)
            $table->foreignId('animal_id')->constrained('animales')->restrictOnDelete();

            // AUTOR DEL REGISTRO (USUARIO AUTENTICADO QUE LO CREA)
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            // TIPO DE ACONTECIMIENTO: LA OBSERVACIÓN GENERAL NO SE CONFUNDE CON UN DIAGNÓSTICO
            $table->enum('tipo', ['observacion', 'diagnostico', 'tratamiento', 'vacunacion', 'revision'])
                ->default('observacion');

            $table->text('descripcion');
            $table->dateTime('fecha');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historiales');
    }
};
