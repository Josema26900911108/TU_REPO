<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
public function up()
{
    Schema::create('reportes_generados', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('fkUsuario');
        $table->string('nombre_archivo');
        $table->string('estado')->default('completado');
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reportes_generados');
    }
};
