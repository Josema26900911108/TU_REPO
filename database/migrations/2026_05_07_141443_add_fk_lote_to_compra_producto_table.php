<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
public function up()
{
    Schema::table('compra_producto', function (Blueprint $table) {
        // Agregamos la columna como BIGINT Unsigned y que permita nulos
        $table->unsignedBigInteger('fkLote')->nullable()->after('fkTienda');
        
        // Opcional: Crear la llave foránea si quieres integridad total
        // $table->foreign('fkLote')->references('id')->on('lotesalarma');
    });
}

public function down()
{
    Schema::table('compra_producto', function (Blueprint $table) {
        $table->dropColumn('fkLote');
    });
}

};
