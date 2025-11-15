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
        Schema::dropIfExists('tienda');

        Schema::create('tienda', function (Blueprint $table) {
            $table->bigIncrements('idTienda');
            $table->string('Nombre'); // 'compra', 'venta', 'apertura', 'cierre'
            $table->text('Direccion')->nullable();
            $table->text('descripcion')->nullable();
            $table->char('EstatusContable', 1);
            $table->string('Telefono');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tienda');
    }
};
