<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('animales', function (Blueprint $table) {
            $table->unsignedSmallInteger('minutos_paseos')->nullable();
            $table->unsignedSmallInteger('minutos_cuidados')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('animales', function (Blueprint $table) {
            $table->dropColumn([
                'minutos_paseos',
                'minutos_cuidados'
            ]);
        });
    }
};