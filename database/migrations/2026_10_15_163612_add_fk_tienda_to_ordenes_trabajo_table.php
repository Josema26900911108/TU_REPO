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
        Schema::table('ordenes_trabajo', function (Blueprint $table) {
            // 🚀 Agrega el campo fkTienda después de la columna 'id' como un entero grande sin signo
            $table->unsignedBigInteger('fkTienda')->nullable()->after('id');
            
            // Opcional: Si tienes una tabla llamada 'tiendas', puedes descomentar la siguiente línea para crear la llave foránea física:
            // $table->foreign('fkTienda')->references('id')->on('tiendas')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ordenes_trabajo', function (Blueprint $table) {
            // Eliminar la columna si se hace un rollback
            $table->dropColumn('fkTienda');
        });
    }
};
