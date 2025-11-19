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
    Schema::create('ArqueoCaja', function (Blueprint $table) {
        $table->bigIncrements('idArqueoCaja');
        $table->decimal('CEF', 12, 2); // Corregido a 2 decimales ||Efectivo al momento del cierre
        $table->decimal('VD', 12, 2); // Corregido a 2 decimales||Suma de ventas diarias ||VD= CEF + CEI + VO - OG - D - CC.
        $table->decimal('VO', 12, 2); // Corregido a 2 decimales||Pagos mediante el resto de medios habilitados
        $table->decimal('D', 12, 2); // Corregido a 2 decimales||Descuentos
        $table->decimal('CC', 12, 2); // Corregido a 2 decimales||Ventas a crédito
        $table->decimal('OG', 12, 2); // Corregido a 2 decimales||Salida de dinero para otras partidas de la empresa
        $table->decimal('CEI', 12, 2); // Corregido a 2 decimales||Saldo inicial de la caja
        $table->decimal('ChCo', 12, 2); // Corregido a 2 decimales||Cheques por cobrar
        $table->decimal('vales', 12, 2); // Corregido a 2 decimales||vales de descuento o promociones aplicadas.

        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ArqueoCaja');
    }
};
