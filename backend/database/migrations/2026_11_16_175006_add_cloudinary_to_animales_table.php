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
        Schema::table('animales', function (Blueprint $table) {
            $table->text('fotografia')->nullable()->change();
            $table->text('imagen')->nullable()->change();

            $table->string('fotografia_public_id')->nullable();
            $table->string('imagen_public_id')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('animales', function (Blueprint $table) {
            $table->dropColumn([
                'fotografia_public_id',
                'imagen_public_id'
            ]);
        });
    }
};
