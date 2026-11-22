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
        // TRABAJADOR ASIGNADO AL ANIMAL E INSTRUCCIONES DE CUIDADO (T17)
        Schema::table('animales', function (Blueprint $table) {
            $table->foreignId('cuidador_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('instrucciones_cuidado')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('animales', function (Blueprint $table) {
            $table->dropForeign(['cuidador_id']);
            $table->dropColumn([
                'cuidador_id',
                'instrucciones_cuidado'
            ]);
        });
    }
};
