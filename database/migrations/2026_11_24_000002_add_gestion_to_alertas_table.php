<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agrega trazabilidad de gestión a las alertas: quién la gestionó y cuándo se resolvió.
     */
    public function up(): void
    {
        Schema::table('alertas', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('observacion_gestion')->constrained()->nullOnDelete();
            $table->timestamp('resuelta_en')->nullable()->after('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('alertas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
            $table->dropColumn('resuelta_en');
        });
    }
};
