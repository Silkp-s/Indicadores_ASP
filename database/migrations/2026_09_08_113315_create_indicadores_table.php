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
        Schema::create('indicadores', function (Blueprint $table) {
        $table->id();
        $table->string('codigo')->unique(); // Ej: MS-01, IAAPS-04
        $table->string('nombre'); // Ej: Evaluación del Desarrollo Psicomotor
        $table->enum('tipo', ['Meta Sanitaria', 'IAAPS', 'Ministerial']); // Según el proyecto
        
        // Fórmulas y Metas 
        $table->text('numerador_description')->nullable(); // Ej: Niños evaluados
        $table->text('denominador_description')->nullable(); // Ej: Total inscritos
        $table->decimal('target_value', 5, 2); // Meta en porcentaje o número (Ej: 85.00)
        

        $table->string('periodicidad'); // Ej: Mensual, Trimestral
        $table->string('fu'); // Ej: REM-P, REM-A, DEIS
        
        $table->boolean('is_actie')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('indicadores');
    }
};
