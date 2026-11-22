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
        // CAMPOS PARA LA BAJA LÓGICA DEL ANIMAL (SE CONSERVA SU HISTORIA)
        Schema::table('animales', function (Blueprint $table) {
            $table->boolean('activo')->default(true);
            $table->date('fecha_baja')->nullable();
            $table->string('motivo_baja')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('animales', function (Blueprint $table) {
            $table->dropColumn([
                'activo',
                'fecha_baja',
                'motivo_baja'
            ]);
        });
    }
};
