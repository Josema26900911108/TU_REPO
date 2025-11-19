<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('Folio', function (Blueprint $table) {
            $table->bigIncrements('idFolio');
            $table->string('cabecera');
            $table->text('descripcion')->nullable();
            $table->char('EstatusContable', 1);
            $table->char('TipoFolio', 1);
            $table->dateTime('FechaCancelacion');
            $table->dateTime('FechaContabilizacion');
            $table->dateTime('FechaAnulacion');
            $table->unsignedBigInteger('fkComprobante');
            $table->unsignedBigInteger('idOrigen');
            $table->char('TipoMovimiento', 3);
            $table->foreign('fkComprobante')->references('id')->on('comprobantes')->onDelete('cascade');
            $table->unsignedBigInteger('fkTienda');
            $table->foreign('fkTienda')->references('idTienda')->on('tienda')->onDelete('cascade');
            $table->unsignedBigInteger('fkUsuario'); // Agregar la columna fkUsuario
            $table->foreign('fkUsuario')->references('id')->on('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('Folio', function (Blueprint $table) {
            $table->dropForeign(['fkFolio']); // Eliminar la clave foránea
            $table->dropColumn('fkFolio');    // Eliminar la columna
            $table->dropForeign(['fkComprobante']); // Eliminar la clave foránea
            $table->dropColumn('fkComprobante');    // Eliminar la columna
            $table->dropForeign(['fkTienda']); // Eliminar la clave foránea
            $table->dropColumn('fkTienda');    // Eliminar la columna
            $table->dropForeign(['fkUsuario']); // Eliminar la clave foránea
            $table->dropColumn('fkUsuario');    // Eliminar la columna
        });
        Schema::dropIfExists('Folio');
    }
};
