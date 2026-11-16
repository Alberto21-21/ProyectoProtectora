<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('animal_asignaciones', function (Blueprint $table) {
            $table->id();

            $table->foreignId('animal_id')
                ->constrained('animales')
                ->cascadeOnDelete();

            $table->foreignId('empleado_id')
                ->constrained('empleados')
                ->restrictOnDelete();

            $table->enum('tipo_asignacion', [
                'responsable',
                'veterinario',
            ]);

            $table->foreignId('asignado_por_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->unique(
                ['animal_id', 'empleado_id', 'tipo_asignacion'],
                'animal_asignacion_unica'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('animal_asignaciones');
    }
};
