<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('DetalleFolio', function (Blueprint $table) {
            $table->bigIncrements('idDetalleFolio');
            $table->decimal('Monto');
            $table->char('Naturaleza',1);
            $table->unsignedBigInteger('fkCuenetaContable');
            $table->foreign('fkCuenetaContable')->references('id')->on('cuentas_contables')->onDelete('cascade');
            $table->unsignedBigInteger('fkFolio');
            $table->foreign('fkFolio')->references('idFolio')->on('Folio')->onDelete('cascade');
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
        Schema::table('DetalleFolio', function (Blueprint $table) {
            $table->dropForeign(['fkFolio']); // Eliminar la clave foránea
            $table->dropColumn('fkFolio');    // Eliminar la columna
            $table->dropForeign(['fkCuenetaContable']); // Eliminar la clave foránea
            $table->dropColumn('fkCuenetaContable');    // Eliminar la columna
            $table->dropForeign(['fkTienda']); // Eliminar la clave foránea
            $table->dropColumn('fkTienda');    // Eliminar la columna
            $table->dropForeign(['fkUsuario']); // Eliminar la clave foránea
            $table->dropColumn('fkUsuario');    // Eliminar la columna
        });
        Schema::dropIfExists('DetalleFolio');
    }
};
