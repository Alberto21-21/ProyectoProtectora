<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seguimientos_solicitudes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('solicitud_id')
                ->constrained('solicitudes')
                ->restrictOnDelete();

            $table->foreignId('responsable_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->string('tipo', 30);
            $table->string('estado_anterior', 30);
            $table->string('estado_nuevo', 30);
            $table->dateTime('fecha');
            $table->text('observaciones');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seguimientos_solicitudes');
    }
};