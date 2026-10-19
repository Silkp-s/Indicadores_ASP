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
        Schema::create('rem_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('indicadores_id')->constrained('indicadores')->cascadeOnDelete();
            $table->string('hoja', 50);
            $table->string('celda_numerador', 255);
            $table->string('celda_denominador', 255)->nullable();
            $table->string('seccion', 100)->nullable();
            $table->text('descripcion')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rem_mappings');
    }
};
