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
    Schema::create('recetas', function (Blueprint $table) {
        $table->id();
        
        // Referencia al producto final (plato o mueble)
        $table->foreignId('producto_padre_id')->constrained('productos')->onDelete('cascade');
        
        // Referencia al insumo o ingrediente
        $table->foreignId('ingrediente_id')->constrained('productos')->onDelete('cascade');
        
        // Cantidad con precisión decimal para medidas pequeñas
        $table->decimal('cantidad', 10, 3); 

        // LLAVE FORÁNEA PERSONALIZADA PARA TIENDA
        // Usamos unsignedBigInteger para asegurar compatibilidad de tipos
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
        Schema::dropIfExists('recetas');
        Schema::table('recetas', function (Blueprint $table) {
            $table->dropForeign(['fkTienda']);
        });
        
    }
};
