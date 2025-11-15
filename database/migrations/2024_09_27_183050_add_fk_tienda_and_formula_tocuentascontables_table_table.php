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
        // Add 'fkTienda' column to the 'comprobantes' table
        Schema::table('cuentas_contables', function (Blueprint $table) {
            $table->string('formula');
            $table->unsignedBigInteger('fkTienda')->nullable();
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
        Schema::table('cuentas_contables', function (Blueprint $table) {
            $table->dropForeign(['fkTienda']); // Eliminar la clave foránea
            $table->dropColumn('fkTienda');    // Eliminar la columna
            $table->dropColumn('formula');
        });
    }
};
