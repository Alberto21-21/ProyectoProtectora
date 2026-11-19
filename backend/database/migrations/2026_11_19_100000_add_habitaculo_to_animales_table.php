<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // HABITÁCULO DONDE ESTÁ ALOJADO EL ANIMAL (T24)
        Schema::table('animales', function (Blueprint $table) {
            $table->foreignId('habitaculo_id')
                ->nullable()
                ->constrained('habitaculos')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        // Primero se quita la clave foránea y luego la columna.
        Schema::table('animales', function (Blueprint $table) {
            $table->dropForeign(['habitaculo_id']);
            $table->dropColumn('habitaculo_id');
        });
    }
};
