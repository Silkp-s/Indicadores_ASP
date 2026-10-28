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
        Schema::create('deis_egresos', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('anio');
            $table->string('pertenencia', 30);
            $table->string('sexo', 20);
            $table->string('grupo_edad', 30);
            $table->string('capitulo_codigo', 15);
            $table->string('capitulo_glosa', 255);
            $table->string('condicion', 15);
            $table->unsignedInteger('egresos')->default(0);
            $table->unsignedBigInteger('dias_estada')->default(0);
            $table->timestamps();

            $table->unique(['anio', 'pertenencia', 'sexo', 'grupo_edad', 'capitulo_codigo', 'condicion'], 'deis_egreso_unico');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deis_egresos');
    }
};
