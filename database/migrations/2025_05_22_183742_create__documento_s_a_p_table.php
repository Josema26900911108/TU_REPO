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
        Schema::create('E', function (Blueprint $table) {
            $table->id();
            $table->string('numero_documento');
            $table->string('referencia_sap');
            $table->string('texto_clase_movimiento_sap');
            $table->string('unidad_medida_base_sap');
            $table->date('fecha_contabilizacion_sap');
            $table->string('cantidad_sap');
            $table->string('clase_movimiento_sap');
            $table->string('centro_sap');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('DocumentoSAP');
        
    }
};
