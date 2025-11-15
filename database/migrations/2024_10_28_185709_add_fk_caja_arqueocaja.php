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
        Schema::table('arqueocaja', function (Blueprint $table) {
            $table->unsignedBigInteger('fkCaja');
            $table->foreign('fkCaja')
            ->references('id')
            ->on('cash_registers') // Verifica el nombre exacto de la tabla
            ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('arqueocaja', function (Blueprint $table) {
            $table->dropForeign(['fkCaja']); // Eliminar la clave foránea
            $table->dropColumn('fkCaja');    // Eliminar la columna
        });
    }
};
