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
    Schema::create('centros_organizacion', function (Blueprint $table) {
        $table->id();

        // Debemos especificar que la referencia es 'idTienda' y no 'id'
        $table->unsignedBigInteger('fkTiendaPrincipal');
        $table->foreign('fkTiendaPrincipal')
              ->references('idTienda')
              ->on('tienda');

        $table->unsignedBigInteger('fkTiendaDependiente');
        $table->foreign('fkTiendaDependiente')
              ->references('idTienda')
              ->on('tienda');

        // Para el centro, si su PK es 'id', puedes usar la forma corta:
        $table->foreignId('fkCentro')->constrained('centro');

        $table->char('status', 1)->default('I');
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('centros_organizacion');
        Schema::table('centros_organizacion', function(Blueprint $table){
            $table->dropForeign(['fkTiendaPrinciapl']);
            $table->dropForeign(['fkTiendaDependiente']);
            $table->dropForeign(['fkCentro']);
        });
    }
};
