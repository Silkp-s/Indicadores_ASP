<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Catálogo de reglas del Motor de Alertas (Módulo 6).
     */
    public function up(): void
    {
        Schema::create('alerta_reglas', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 10)->unique(); // Ej: REG-01
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->enum('severidad', ['Crítica', 'Advertencia', 'Informativa']);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alerta_reglas');
    }
};
