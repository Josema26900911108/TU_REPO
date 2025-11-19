<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

        public function up(): void
    {
        Schema::create('ETA', function (Blueprint $table) {
            $table->id();
            $table->string('Orden');
            $table->string('SKU');
            $table->text('Descripcion');
            $table->float('Cantidad');
            $table->string('Serie');
            $table->string('MAC1');
            $table->string('MAC2');
            $table->string('MAC3');
            $table->string('TIPO_DE_SERVICIO');
            $table->string('TIPO_DE_ORDEN');
            $table->string('CENTRO');
            $table->string('EMPLEADO');



            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::table('ETA', function (Blueprint $table) {

        });

        Schema::dropIfExists('ETA');
    }
};
