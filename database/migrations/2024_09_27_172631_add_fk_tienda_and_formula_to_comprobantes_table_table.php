<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add 'fkTienda' column to the 'comprobantes' table
        Schema::table('comprobantes', function (Blueprint $table) {
            $table->unsignedBigInteger('fkTienda')->nullable();
            $table->foreign('fkTienda')
                ->references('idTienda')
                ->on('tienda') // Verifica el nombre exacto de la tabla
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        // Drop 'fkTienda' foreign key and column from the 'comprobantes' table
        Schema::table('comprobantes', function (Blueprint $table) {
            $table->dropForeign(['fkTienda']); // Eliminar la clave foránea
            $table->dropColumn('fkTienda');    // Eliminar la columna
        });
    }
};
