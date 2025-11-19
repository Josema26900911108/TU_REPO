<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

        public function up(): void
    {
        Schema::create('MaterialManoObra', function (Blueprint $table) {
            $table->id();
            $table->string('SKU');
            $table->text('Descripcion');;
            $table->string('TIPO');
            $table->string('unidadmedida');
            $table->string('CATEGORIA');
            $table->float('COSTOPAGO');
            $table->float('CATEGORIACOBRO');

            $table->timestamps();
        });
    }

    public function down(): void
    {

        Schema::dropIfExists('MaterialManoObra');
    }
};
