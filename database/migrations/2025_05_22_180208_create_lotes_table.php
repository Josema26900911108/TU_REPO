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
Schema::create('lotes', function (Blueprint $table) {
    $table->id();
    $table->string('codigo'); // Lote real
    $table->date('fecha_vencimiento')->nullable(); // si perecedero
    $table->foreignId('fkProductos')->constrained('productos');
    $table->timestamps();
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {

            Schema::table('lotes', function (Blueprint $table) {
            $table->dropForeign(['fkProductos']); // Eliminar la clave foránea
            $table->dropColumn('fkProductos');    // Eliminar la columna
        });
    }
};
