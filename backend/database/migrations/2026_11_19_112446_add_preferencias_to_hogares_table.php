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
        Schema::table('hogares', function (Blueprint $table) {
            $table->unsignedSmallInteger('minutos_paseos')->nullable();
            $table->unsignedSmallInteger('minutos_cuidados')->nullable();

            $table->string('nivel_actividad', 20)->nullable();
            $table->string('especie_preferida', 20)->nullable();
            $table->string('tamano_preferido', 20)->nullable();

            $table->unsignedSmallInteger('edad_minima')->nullable();
            $table->unsignedSmallInteger('edad_maxima')->nullable();

            $table->boolean('acepta_necesidades_especiales')->nullable();
            $table->text('cuidados_asumibles')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('hogares', function (Blueprint $table) {
            $table->dropColumn([
                'minutos_paseos',
                'minutos_cuidados',
                'nivel_actividad',
                'especie_preferida',
                'tamano_preferido',
                'edad_minima',
                'edad_maxima',
                'acepta_necesidades_especiales',
                'cuidados_asumibles'
            ]);
        });
    }
};
