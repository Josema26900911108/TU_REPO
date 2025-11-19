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
    Schema::create('CajaMetodoPago', function (Blueprint $table) {
        $table->bigIncrements('idCajaMetodoPago');
        $table->integer('fkCaja');
        $table->integer('fkMetodoPago');
        $table->decimal('Monto', 12, 2); // Corregido a 2 decimales
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(table: 'CajaMetodoPago');
    }
};
