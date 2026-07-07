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
        // Cambiado a 'movimientomaterial' para que coincida con tu requerimiento original de base de datos
        Schema::create('movimientomaterial', function (Blueprint $table) {
            $table->bigIncrements('id'); // bigint(20) UN AI PK
            
            // 1. PRIMERO DECLARAMOS LA COLUMNA (Se pone nullable por seguridad en la importación masiva)
            $table->unsignedBigInteger('fkTienda')->nullable();
            
            $table->string('serie')->nullable();
            $table->string('SKU')->nullable();
            $table->string('almacen')->nullable();
            $table->string('Lote')->nullable();
            $table->string('MAC1')->nullable();
            $table->string('MAC2')->nullable();
            $table->string('MAC3')->nullable();
            $table->string('ESTATUS')->nullable();
            $table->double('COSTO', 8, 2)->nullable(); // double(8,2)
            $table->string('CENTRO')->nullable();
            $table->date('Modificado_el')->nullable();
            $table->string('Modificado_por')->nullable();
            $table->date('Creado_el')->nullable();
            $table->string('Creado_por')->nullable();
            $table->string('TIPO')->nullable();
            $table->string('unidadmedida')->nullable();
            $table->string('TIPOMOVIMIENTO')->nullable();
            $table->timestamps(); // Crea 'created_at' y 'updated_at' automáticamente

            // 2. LUEGO SE DEFINE LA LLAVE FORÁNEA (Asegúrate de que tu tabla destino se llame 'tienda' o 'tiendas')
            $table->foreign('fkTienda')->references('id')->on('tienda')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movimientomaterial');
    }
};
