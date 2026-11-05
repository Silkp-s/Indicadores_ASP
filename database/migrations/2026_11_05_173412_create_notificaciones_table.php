<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Notificaciones persistentes de acciones realizadas por los usuarios.
     */
    public function up(): void
    {
        Schema::create('notificaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('tipo', 20)->default('success'); // success | error | warning | info
            $table->text('mensaje');
            $table->timestamp('leida_en')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'leida_en']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notificaciones');
    }
};
