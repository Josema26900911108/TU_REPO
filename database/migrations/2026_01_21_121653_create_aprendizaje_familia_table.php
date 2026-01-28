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
        Schema::create('aprendizaje_familia', function (Blueprint $table) {
            $table->string('tipo_servicio', 100);
            $table->bigInteger('familia_id');
            $table->integer('veces_usado')->default(0);

            // Definir clave primaria compuesta
            $table->primary(['tipo_servicio', 'familia_id']);

            // Si deseas una relación con otra tabla (opcional)
            // $table->foreign('familia_id')->references('id')->on('familias')->onDelete('cascade');

            $table->timestamps(); // Opcional, agrega created_at y updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('aprendizaje_familia');
    }
};
