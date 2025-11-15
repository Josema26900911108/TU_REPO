<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

      public function up(): void
    {
        Schema::create('descuentotecnico', function (Blueprint $table) {
            $table->id();
            $table->string('Orden');
            $table->string('SKU');
            $table->text('Descripcion');
            $table->text('OBS');
            $table->float('Cantidad');
            $table->float('COSTOPAGO');

            $table->timestamps();


        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {

            Schema::table('descuentotecnico', function (Blueprint $table) {

        });

        Schema::dropIfExists('descuentotecnico');
    }

};
