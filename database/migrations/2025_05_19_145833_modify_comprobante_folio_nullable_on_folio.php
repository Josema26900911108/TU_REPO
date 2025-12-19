<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

        public function up(): void
    {
        Schema::table('Folio', function (Blueprint $table) {
            // Eliminar la foreign key
            $table->dropForeign(['fkComprobante']);

            // Hacer que acepte NULL (asegúrate del tipo de dato, aquí uso unsignedBigInteger)
            $table->unsignedBigInteger('fkComprobante')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('Folio', function (Blueprint $table) {
            // Revertir a NOT NULL y volver a agregar la clave foránea
            $table->unsignedBigInteger('fkComprobante')->nullable(false)->change();
            $table->foreign('fkComprobante')->references('id')->on('comprobantes');
        });
    }

};
