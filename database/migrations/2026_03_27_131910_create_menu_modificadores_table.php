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
     Schema::dropIfExists('menu_modificadores'); 

    Schema::create('menu_modificadores', function (Blueprint $table) {
        $table->id();
        
        // Relación con el Menú (A qué plato pertenece este extra)
        $table->foreignId('menu_id')->constrained('menus')->onDelete('cascade');
        
        // Relación con el Producto de Inventario (Qué se va a descontar)
        $table->foreignId('producto_id')->constrained('productos'); 
        $table->enum('tipo', ['unico', 'multiple', 'texto_libre'])->default('unico');
        
        // Datos comerciales
        $table->string('nombre_extra'); // Ej: "Doble Carne", "Papas Grandes"
        $table->decimal('precio_adicional', 10, 2)->default(0); // Cuánto más paga el cliente
        
        // Datos de inventario
        $table->decimal('cantidad_descontar', 10, 3); // Ej: 1.000 (1 unidad) o 0.100 (100gr)
        
        // Lógica de negocio
        $table->boolean('es_obligatorio')->default(false); // Ej: Término de la carne
        $table->integer('limite_maximo')->default(1); // Cuántas veces puede pedir este extra
        $table->integer('minimo')->default(0); // 0 si es opcional
        $table->boolean('activo')->default(true);
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
        Schema::dropIfExists('menu_modificadores');
                Schema::table('recetas', function (Blueprint $table) {
            $table->dropForeign(['fkTienda']);
        });
    }
};
