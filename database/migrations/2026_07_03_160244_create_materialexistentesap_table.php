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
        // Verifica si la tabla ya existe antes de intentar crearla
        if (!Schema::hasTable('materialexistentesap')) {
            Schema::create('materialexistentesap', function (Blueprint $table) {
                $table->bigIncrements('id'); 
                
                $table->unsignedBigInteger('fkTienda')->nullable()->index(); // ⚡ Índice para filtros por Tienda
                $table->string('serie')->nullable()->index(); // ⚡ Índice crucial: Las series deben buscarse al instante
                $table->string('SKU')->nullable()->index();   // ⚡ Índice para búsquedas rápidas de materiales
                $table->string('almacen')->nullable();
                $table->string('Lote')->nullable()->index();  // ⚡ Índice para trazabilidad de lotes SAP
                $table->string('MAC1')->nullable()->index();  // ⚡ Índice para validación de hardware
                $table->string('MAC2')->nullable();
                $table->string('MAC3')->nullable();
                $table->string('ESTATUS')->nullable();
                $table->double('COSTO', 12, 2)->nullable(); // 💡 Optimización: Ampliado a (12,2) por si entran montos grandes de inventario
                $table->string('CENTRO')->nullable()->index(); // ⚡ Índice: Evita colapsos en tus consultas GROUP BY de autómatas
                $table->date('Modificado_el')->nullable();
                $table->string('Modificado_por')->nullable();
                $table->date('Creado_el')->nullable();
                $table->string('Creado_por')->nullable();
                $table->string('TIPO')->nullable();
                $table->string('unidadmedida')->nullable();
                $table->string('TIPOMOVIMIENTO')->nullable();
                $table->timestamps(); 

                // Restricción de integridad referencial
                $table->foreign('fkTienda')->references('id')->on('tienda')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('materialexistentesap');
    }
};
