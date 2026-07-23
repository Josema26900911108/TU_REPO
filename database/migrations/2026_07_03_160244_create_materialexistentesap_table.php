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
        Schema::create('materialexistentesap', function (Blueprint $table) {
            // Clave primaria
            $table->id(); 
            
            // Relación externa (llave foránea)
            $table->foreignId('tienda_id')
                  ->nullable()
                  ->constrained('tienda')
                  ->onDelete('cascade');
            
            // Índices para búsquedas de alta velocidad
            $table->string('serie')->nullable()->index(); 
            $table->string('sku')->nullable()->index();   
            $table->string('lote')->nullable()->index();  
            $table->string('mac1')->nullable()->index();  
            $table->string('centro')->nullable()->index(); 

            // Campos informativos y técnicos
            $table->string('almacen')->nullable();
            $table->string('mac2')->nullable();
            $table->string('mac3')->nullable();
            $table->string('estatus')->nullable();
            $table->string('tipo')->nullable();
            $table->string('unidad_medida')->nullable();
            $table->string('tipo_movimiento')->nullable();

            // Precisión financiera
            $table->decimal('costo', 12, 2)->nullable(); 

            // Auditoría y control
            $table->string('creado_por')->nullable();
            $table->string('modificado_por')->nullable();
            $table->timestamps(); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('material_existente_sap');
    }
};
