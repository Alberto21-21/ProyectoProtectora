<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('disponibilidades', function (Blueprint $table) {
            $table->unsignedTinyInteger('dia_semana')
                ->nullable()
                ->after('empleado_id');

            $table->time('hora_inicio')->nullable()->after('dia_semana');
            $table->time('hora_fin')->nullable()->after('hora_inicio');

            $table->dateTime('fecha_inicio')->nullable()->change();
            $table->dateTime('fecha_fin')->nullable()->change();

            $table->unique(
                ['empleado_id', 'dia_semana'],
                'disponibilidades_empleado_dia_unique'
            );
        });
    }

    public function down(): void
    {
        // LAS FILAS DE JORNADA SEMANAL NO TIENEN FECHAS: SE BORRAN ANTES
        // DE VOLVER A EXIGIR fecha_inicio Y fecha_fin
        DB::table('disponibilidades')->whereNotNull('dia_semana')->delete();

        Schema::table('disponibilidades', function (Blueprint $table) {
            $table->dropUnique('disponibilidades_empleado_dia_unique');

            $table->dropColumn([
                'dia_semana',
                'hora_inicio',
                'hora_fin',
            ]);

            $table->dateTime('fecha_inicio')->nullable(false)->change();
            $table->dateTime('fecha_fin')->nullable(false)->change();
        });
    }
};