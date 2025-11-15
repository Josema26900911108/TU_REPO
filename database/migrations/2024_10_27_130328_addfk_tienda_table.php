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
        Schema::table('metodopago', function (Blueprint $table) {
            $table->unsignedBigInteger('fkTienda');
            $table->foreign('fkTienda')
            ->references('idTienda')
            ->on('tienda') // Verifica el nombre exacto de la tabla
            ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('metodopago', function (Blueprint $table) {
            $table->dropForeign(['fkTienda']); // Eliminar la clave foránea
            $table->dropColumn('fkTienda');    // Eliminar la columna
        });
    }
};
