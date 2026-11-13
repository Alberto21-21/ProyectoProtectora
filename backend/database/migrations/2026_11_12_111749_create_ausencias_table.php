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
         Schema::create('ausencias', function (Blueprint $table) {
        $table->id();
        $table->foreignId('empleado_id')->constrained('empleados')->cascadeOnDelete();
        $table->dateTime('fecha_inicio');
        $table->dateTime('fecha_fin');
        $table->string('motivo', 255)->nullable();
        $table->timestamps();

        $table->index(['empleado_id', 'fecha_inicio', 'fecha_fin']);
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ausencias');
    }
};
