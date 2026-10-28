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
        Schema::create('deis_atenciones_urgencia', function (Blueprint $table) {
            $table->id();
            $table->string('codigo_establecimiento', 20);
            $table->string('nombre_establecimiento');
            $table->string('tipo_establecimiento', 50);
            $table->unsignedSmallInteger('id_causa');
            $table->string('glosa_causa', 255);
            $table->unsignedSmallInteger('anio');
            $table->unsignedTinyInteger('semana');
            $table->unsignedInteger('total')->default(0);
            $table->unsignedInteger('menores_1')->default(0);
            $table->unsignedInteger('de_1_a_4')->default(0);
            $table->unsignedInteger('de_5_a_14')->default(0);
            $table->unsignedInteger('de_15_a_64')->default(0);
            $table->unsignedInteger('de_65_y_mas')->default(0);
            $table->timestamps();

            $table->unique(['codigo_establecimiento', 'id_causa', 'anio', 'semana'], 'deis_urgencia_unica');
            $table->index(['anio', 'id_causa'], 'deis_urgencia_anio_causa');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deis_atenciones_urgencia');
    }
};
