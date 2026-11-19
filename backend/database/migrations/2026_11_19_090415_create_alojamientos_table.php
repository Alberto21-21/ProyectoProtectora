<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alojamientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('animal_id')->constrained('animales')->onDelete('cascade');
            $table->foreignId('habitaculo_origen_id')->nullable()->constrained('habitaculos')->nullOnDelete();
            $table->foreignId('habitaculo_destino_id')->constrained('habitaculos')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // Responsable
            $table->timestamp('fecha_movimiento')->useCurrent();
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alojamientos');
    }
};