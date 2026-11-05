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
        Schema::create('indicador_medidas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('indicadores_id')->constrained()->cascadeOnDelete();
            $table->foreignId('establecimientos_id')->constrained()->cascadeOnDelete();

            // Período de medición
            $table->integer('ano'); // Ej: 2026
            $table->tinyInteger('mes'); // Ej: 9 (Septiembre)

            // Datos ingresados
            $table->decimal('numerador_value', 10, 2)->default(0);
            $table->decimal('denominador_value', 10, 2)->default(0);

            // El porcentaje de avance actual físico (calculado o estático)
            $table->decimal('porcentaje_cumplimiento', 5, 2)->default(0.00);

            // Estado proyectado para el sistema de alertas (Módulo 6)[cite: 1]
            $table->enum('status', ['Cumpliendo', 'En Riesgo', 'Crítico'])->default('Cumpliendo');

            $table->timestamps();

            // Evitar duplicados del mismo indicador para un CESFAM en un mes específico
            $table->unique(['indicadores_id', 'establecimientos_id', 'ano', 'mes'], 'measurement_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('indicador_medidas');
    }
};
