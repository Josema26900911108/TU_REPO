<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
public function up(): void
{

    Schema::dropIfExists('producto_regla_precio'); 
    Schema::dropIfExists('reglas_precios'); 
    Schema::create('reglas_precios', function (Blueprint $table) {
        $table->id();
        $table->string('nombre'); // Ej: "Precio Mayorista", "Promo 3x2", "Bono Lanzamiento"
        
        // --- ACTIVADORES (Trigger) ---
        $table->enum('tipo_regla', [
            'escala_cantidad',  // Ej: De 3 a 10 unidades, precio X
            'bonificacion',     // Ej: Compra 3, lleva 1 gratis (3+1)
            'combo_mixto',      // Ej: Compra A + B y el total es Z
            'descuento_fijo'    // Ej: -10% por temporada
        ]);

        $table->integer('cantidad_minima')->default(1);
        $table->integer('cantidad_paso')->nullable(); // Para el 3x2, el paso es 3
            
        // --- EFECTO (Action) ---
        $table->enum('tipo_beneficio', ['precio_fijo', 'porcentaje', 'unidad_gratis']);
        $table->decimal('valor_beneficio', 15, 4); // Puede ser $10.50, 15 (por ciento) o 1 (unidad)
        
        // --- VALIDEZ ---
        $table->dateTime('fecha_inicio')->nullable();
        $table->dateTime('fecha_fin')->nullable();
        $table->boolean('prioritaria')->default(false); // Si hay 2 reglas, ¿cuál manda?
        $table->unsignedBigInteger('fkTienda');
        $table->foreign('fkTienda')
              ->references('idTienda') // <-- Debe coincidir con la PK de tu tabla 'tienda'
              ->on('tienda')
              ->onDelete('cascade');
        $table->timestamps();
    });

    Schema::create('producto_regla_precio', function (Blueprint $table) {
        $table->id();
        // Asegúrate de especificar los nombres de las tablas en constrained()
        $table->foreignId('producto_id')->constrained('productos')->onDelete('cascade');
        $table->foreignId('regla_precio_id')->constrained('reglas_precios')->onDelete('cascade');
    });
}

public function down(): void
{
    // 1. Borrar primero la tabla pivot (la que tiene las llaves foráneas)
    Schema::dropIfExists('producto_regla_precio');

    // 2. Borrar la tabla principal
    Schema::dropIfExists('reglas_precios');
    
    // Nota: Si 'recetas' no tiene nada que ver con esta migración, 
    // borra las líneas de abajo para evitar errores de "tabla no encontrada".
}


};
