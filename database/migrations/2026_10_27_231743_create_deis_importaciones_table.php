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
        Schema::create('deis_importaciones', function (Blueprint $table) {
            $table->id();
            $table->string('dataset', 30)->index();
            $table->string('origen', 500);
            $table->string('estado', 20);
            $table->unsignedBigInteger('filas_leidas')->default(0);
            $table->unsignedInteger('filas_comuna')->default(0);
            $table->unsignedInteger('registros_guardados')->default(0);
            $table->text('mensaje')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('iniciado_en')->nullable();
            $table->timestamp('finalizado_en')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deis_importaciones');
    }
};
