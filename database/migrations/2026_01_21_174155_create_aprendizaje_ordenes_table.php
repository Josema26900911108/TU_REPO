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
        Schema::create('aprendizaje_ordenes', function (Blueprint $table) {
            $table->string('tipo_servicio', 100)->primary(); // Clave primaria simple
            $table->integer('total_ordenes')->default(0);
            $table->timestamps(); // Opcional, para created_at y updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('aprendizaje_ordenes');
    }
};
