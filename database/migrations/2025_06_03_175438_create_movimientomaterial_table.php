<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
          public function up(): void
    {
        Schema::create('movimientomaterial', function (Blueprint $table) {
            $table->id();
            $table->float('serie');
            $table->string('SKU');
            $table->string('almacen');
            $table->string('Lote');
            $table->string('MAC1');
            $table->string('MAC2');
            $table->string('MAC3');
            $table->string('ESTATUS');
            $table->float('COSTO');
            $table->string('CENTRO');
            $table->date('Modificado_el');
            $table->string('Modificado_por');
            $table->date('Creado_el');
            $table->string('Creado_por');
            $table->string('TIPO');
            $table->string('unidadmedida');
            $table->string('TIPOMOVIMIENTO');

            $table->timestamps();

        });
    }

    public function down(): void
    {
            Schema::table('movimientomaterial', function (Blueprint $table) {
        });

        Schema::dropIfExists('movimientomaterial');

    }
};
