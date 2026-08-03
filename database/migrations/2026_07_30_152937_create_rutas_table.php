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
        // ESTE ARCHIVO SOLO CREA LA TABLA BASE DE RUTAS
        Schema::create('rutas', function (Blueprint $table) {
            $table->id(); 
            $table->string('nombre');
            $table->string('tipo_ciclo'); // 'semanal', 'quincenal', 'mensual'
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rutas');
    }
};
