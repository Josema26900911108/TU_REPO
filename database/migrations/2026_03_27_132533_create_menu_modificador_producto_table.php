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
    Schema::dropIfExists('menu_modificador_producto'); 
    Schema::create('menu_modificador_producto', function (Blueprint $table) {
        $table->id();
        
        // Relaciones (Asegúrate que 'productos' sea el nombre de tu tabla de items)
        $table->foreignId('producto_id')->constrained('productos')->onDelete('cascade');
        $table->foreignId('menu_modificador_id')->constrained('menu_modificadores')->onDelete('cascade');

        // --- FLEXIBILIDAD UNIVERSAL ---
        $table->decimal('precio_override', 10, 2)->nullable(); // Si el precio cambia solo para ESTE producto
        $table->integer('orden_visualizacion')->default(0);    // Para que salgan en orden (ej: 1. Término, 2. Acompañamiento)
        $table->boolean('predefinido')->default(false);        // Si el modificador ya viene incluido por defecto
                $table->unsignedBigInteger('fkTienda');
        $table->foreign('fkTienda')
              ->references('idTienda') // <-- Debe coincidir con la PK de tu tabla 'tienda'
              ->on('tienda')
              ->onDelete('cascade');
        
        $table->timestamps();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('menu_modificador_producto');
                        Schema::table('menu_modificador_producto', function (Blueprint $table) {
            $table->dropForeign(['fkTienda']);
        });
    }
};
