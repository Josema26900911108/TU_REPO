<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
 public function up(): void
    {
        Schema::create('combinacion_familia', function (Blueprint $table) {
            $table->string('tipo_servicio', 100);
            $table->bigInteger('familia_a');
            $table->bigInteger('familia_b');
            $table->integer('veces_juntos')->default(0);

            // Definir clave primaria compuesta triple
            $table->primary(['tipo_servicio', 'familia_a', 'familia_b']);

            // Índices adicionales para mejorar consultas
            $table->index(['tipo_servicio', 'familia_a']);
            $table->index(['tipo_servicio', 'familia_b']);

            // Opcional: relaciones con tabla familias
            // $table->foreign('familia_a')->references('id')->on('familias');
            // $table->foreign('familia_b')->references('id')->on('familias');

            $table->timestamps(); // Opcional
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('combinacion_familia');
    }
};
