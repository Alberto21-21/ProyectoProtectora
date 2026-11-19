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
        Schema::create('hogares', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->unique()
                ->constrained('users')
                ->cascadeOnDelete();

            $table->string('tipo_vivienda', 50);
            $table->decimal('superficie', 8, 2);

            $table->boolean('tiene_jardin');
            $table->boolean('tiene_terraza');
            $table->decimal('superficie_exterior', 8, 2)->nullable();

            $table->unsignedSmallInteger('numero_personas');
            $table->boolean('hay_ninos');
            $table->json('edades_ninos')->nullable();

            $table->boolean('hay_otros_animales');
            $table->text('otros_animales')->nullable();

            $table->text('experiencia');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hogares');
    }
};
