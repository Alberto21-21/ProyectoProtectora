<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // HABITÁCULOS DE CADA ZONA (T23)
        Schema::create('habitaculos', function (Blueprint $table) {
            $table->id();
            $table->string('identificador', 50)->unique();

            // NO SE PUEDE BORRAR UNA ZONA QUE TENGA HABITÁCULOS
            $table->foreignId('zona_id')->constrained('zonas')->restrictOnDelete();

            $table->unsignedInteger('capacidad');
            $table->string('estado_limpieza', 40)->default('Limpio');
            $table->string('estado_mantenimiento', 40)->default('Operativo');
            $table->date('fecha_ultima_limpieza')->nullable();
            $table->date('fecha_proxima_limpieza')->nullable();
            $table->text('observaciones')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('habitaculos');
    }
};
