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
        Schema::create('establecimientos', function (Blueprint $table) {
        $table->id();
        $table->string('nombre'); // Ej: San Vicente, Bellavista
        $table->enum('tipo', ['CESFAM', 'CECOSF', 'SAR', 'SAPU', 'DAS']); // Tipo de recinto
        $table->integer('poblacion inscrita')->default(0); // (visto en Centro de Mando)
        $table->boolean('is_active')->default(true);
        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('establecimientos');
    }
};
