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
     Schema::dropIfExists('menus'); 

    Schema::create('menus', function (Blueprint $table) {
        $table->id();
        
        // Relación con el Producto Terminado (el plato o mueble)
        $table->foreignId('producto_id')->constrained('productos')->onDelete('cascade');
        
        // Clasificación comercial
        $table->string('nombre_menu'); // Nombre comercial (Ej: "Combo Familiar Especial")
        $table->text('descripcion_comercial')->nullable(); // Para la carta/menú digital
        
        // Precios y Costos
        $table->decimal('precio_venta', 12, 2);
        $table->decimal('costo_receta', 12, 2)->default(0); // Se actualiza desde RecetaController
        $table->decimal('margen_ganancia', 5, 2)->nullable(); // Porcentaje (Ej: 30.00%)
        
        // Control de disponibilidad (SAP Style)
        $table->boolean('disponible')->default(true);
        $table->boolean('es_promocion')->default(false);
        $table->date('valido_desde')->nullable();
        $table->date('valido_hasta')->nullable();
        // Atributos de Restaurante/Fábrica
        $table->integer('tiempo_preparacion_minutos')->default(15);
        $table->enum('prioridad', ['baja', 'normal', 'alta'])->default('normal');
        $table->string('imagen_menu')->nullable(); // Foto para el cliente
        $table->unsignedBigInteger('fkTienda');
        $table->foreign('fkTienda')
              ->references('idTienda') // <-- Debe coincidir con la PK de tu tabla 'tienda'
              ->on('tienda')
              ->onDelete('cascade');
        // Para comandas/taller
        $table->string('estacion_preparacion')->nullable(); // Ej: "Cocina Caliente", "Parrilla", "Carpintería"

        $table->timestamps();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('menus');
        Schema::table('recetas', function (Blueprint $table) {
            $table->dropForeign(['fkTienda']);
        });
    }
};
