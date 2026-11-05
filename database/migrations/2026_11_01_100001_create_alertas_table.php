<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bandeja de incidentes detectados por el Motor de Alertas (Módulo 6).
     */
    public function up(): void
    {
        Schema::create('alertas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alerta_regla_id')->constrained('alerta_reglas')->cascadeOnDelete();
            // Nulo cuando la alerta es del establecimiento completo (ej: REG-04 sin carga, REG-10 acumulación).
            $table->foreignId('indicadores_id')->nullable()->constrained('indicadores')->nullOnDelete();
            $table->foreignId('establecimientos_id')->constrained('establecimientos')->cascadeOnDelete();

            $table->integer('ano');
            $table->tinyInteger('mes');

            $table->text('mensaje');
            $table->decimal('brecha_detectada', 8, 2)->nullable(); // Puntos porcentuales bajo la meta (o cantidad, en REG-10)

            $table->enum('estado', ['Pendiente', 'Revisada', 'Resuelta'])->default('Pendiente');
            $table->text('observacion_gestion')->nullable();

            $table->timestamps();

            // Bandeja y filtros habituales
            $table->index(['ano', 'mes', 'estado']);
            $table->index('establecimientos_id');
            $table->index(['alerta_regla_id', 'ano', 'mes']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alertas');
    }
};
