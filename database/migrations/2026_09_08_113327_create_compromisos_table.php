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
        Schema::create('compromisos', function (Blueprint $table) {
        $table->id();
        $table->string('codigo')->unique(); // Ej: CG-L1, CG-E1
        $table->string('nombre'); // Ej: Auditoría Interna de Fichas
        $table->enum('tipo', ['Local', 'Externo (SST)']); // Tipo de compromiso
        $table->string('responsable'); // Ej: Referente Calidad DA
        
        $table->decimal('meta_anual', 10, 2); // Meta Anual (Ej: 100, 15, 12)
        $table->string('target_unit')->nullable(); // Ej: Fichas, Días, Talleres
        
        $table->boolean('is_active')->default(true);
        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('compromisos');
    }
};
