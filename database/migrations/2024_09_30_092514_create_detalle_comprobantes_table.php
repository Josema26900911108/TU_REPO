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
        Schema::create('detalle_comprobantes', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('formula');
            $table->decimal('valorminimo',12,2);
            $table->unsignedBigInteger('fkComprobante')->nullable();
            $table->foreign('fkComprobante')->references('id')->on('comprobantes')->onDelete('cascade');
            $table->unsignedBigInteger('fkCuentaContable')->nullable();
            $table->foreign('fkCuentaContable')->references('id')->on('cuentas_contables')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('detalle_comprobantes', function (Blueprint $table) {
            $table->dropForeign(['fkComprobante']); // Eliminar la clave foránea
            $table->dropColumn('fkComprobante');    // Eliminar la columna
            $table->dropForeign(['fkCuentaContable']); // Eliminar la clave foránea
            $table->dropColumn('fkCuentaContable');    // Eliminar la columna
        });
    }
};
