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
                Schema::create('InstalacionTecnico', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fkTecnico')->constrained('productos');
            $table->foreignId('fkMProducto')->constrained('productos');
            $table->float('cantidad_usada');
            $table->date('fecha');
            $table->string('ubicacion');
            $table->string('referencia_instalacion');
            $table->text('observaciones');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {

                Schema::table('InstalacionTecnico', function (Blueprint $table) {
            $table->dropForeign(['fkMProducto']); // Eliminar la clave foránea
            $table->dropColumn('fkMProducto');    // Eliminar la columna
            $table->dropForeign(['fkTecnico']); // Eliminar la clave foránea
            $table->dropColumn('fkTecnico');    // Eliminar la columna
            });
    }
};
