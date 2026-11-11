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
        // DATOS GENERALES DE LA FICHA
        if (!Schema::hasColumn('animales', 'peso')) {
            Schema::table('animales', function (Blueprint $table) {
                $table->decimal('peso', 5, 2)->nullable();
            });
        }

        if (!Schema::hasColumn('animales', 'caracter')) {
            Schema::table('animales', function (Blueprint $table) {
                $table->string('caracter')->nullable();
            });
        }

        // DATOS CLÍNICOS RESTRINGIDOS (SOLO VETERINARIO Y ADMINISTRADOR)
        if (!Schema::hasColumn('animales', 'necesidades_especiales')) {
            Schema::table('animales', function (Blueprint $table) {
                $table->text('necesidades_especiales')->nullable();
            });
        }

        if (!Schema::hasColumn('animales', 'observaciones_clinicas')) {
            Schema::table('animales', function (Blueprint $table) {
                $table->text('observaciones_clinicas')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::table('animales', function (Blueprint $table) {
            $table->dropColumn([
                'peso',
                'caracter',
                'necesidades_especiales',
                'observaciones_clinicas'
            ]);
        });
    }
};