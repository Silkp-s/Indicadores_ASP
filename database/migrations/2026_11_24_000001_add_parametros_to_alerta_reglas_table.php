<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agrega la columna de parámetros (umbrales) configurables por regla.
     */
    public function up(): void
    {
        Schema::table('alerta_reglas', function (Blueprint $table) {
            $table->json('parametros')->nullable()->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('alerta_reglas', function (Blueprint $table) {
            $table->dropColumn('parametros');
        });
    }
};
