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
        Schema::dropIfExists('caja');

        Schema::create('caja', function (Blueprint $table) {
            $table->id();
            $table->string('tipo_movimiento'); // 'compra', 'venta', 'apertura', 'cierre'
            $table->decimal('monto', 10, 2); // Monto del movimiento
            $table->decimal('saldo', 10, 2); // Saldo restante en caja
            $table->text('descripcion')->nullable();
            $table->char('EstatusContable', 1);
            $table->integer('idVenta')->nullable(); // opcional para ventas
            $table->integer('idCompra')->nullable(); // opcional para compras
            $table->integer('idArqueoCaja')->nullable(); // opcional para arqueo
            $table->integer('idCaja')->nullable(); // opcional para caja
            $table->char('EstatusArqueo', 1);
            $table->timestamps();
        });
    }



    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('caja');
    }
};
