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
        Schema::create('deis_defunciones', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('anio');
            $table->unsignedTinyInteger('mes');
            $table->string('sexo', 20);
            $table->string('grupo_edad', 30);
            $table->string('capitulo_codigo', 15);
            $table->string('capitulo_glosa', 255);
            $table->unsignedInteger('total')->default(0);
            $table->timestamps();

            $table->unique(['anio', 'mes', 'sexo', 'grupo_edad', 'capitulo_codigo'], 'deis_defuncion_unica');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deis_defunciones');
    }
};
