<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
public function up(): void
{
    Schema::table('movimiento_materiales', function (Blueprint $table) {
        // 1. Eliminar la restricción de llave foránea actual
        // Laravel por defecto la nombra: tabla_columna_foreign
        $table->dropForeign(['fkLotes']);

        // 2. Volver a crearla apuntando a la tabla correcta 'lotesalarma'
        $table->foreign('fkLotes')
              ->references('id')
              ->on('lotesalarma')
              ->onDelete('set null'); // O la acción que prefieras
    });
}

public function down(): void
{
    Schema::table('movimiento_materiales', function (Blueprint $table) {
        $table->dropForeign(['fkLotes']);
        $table->foreign('fkLotes')->references('id')->on('lotes');
    });
}

};
