<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // RESTRICCIONES DEL HABITÁCULO: SOLO GATOS, AISLAMIENTO... (T24)
        Schema::table('habitaculos', function (Blueprint $table) {
            $table->string('restricciones', 255)->nullable();
        });
    }

    public function down(): void
    {
         // Esto deja la tabla como estaba antes de aplicar la migración.
        Schema::table('habitaculos', function (Blueprint $table) {
            $table->dropColumn('restricciones');
        });
    }
};
