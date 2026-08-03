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
        // El nombre de la tabla cambia aquí a 'ruta_dia_cliente' para hacer match con tu controlador y vista
        Schema::create('ruta_dia_cliente', function (Blueprint $table) {
            $table->id();
            
            // Relación con la tabla rutas (Ya creada en el archivo anterior)
            $table->foreignId('ruta_id')
                  ->constrained('rutas')
                  ->onDelete('cascade');
            
            // Relación con tu tabla de clientes
            $table->foreignId('cliente_id')
                  ->constrained('clientes')
                  ->onDelete('cascade');
                  
            // Atributos obligatorios para definir el ciclo
            $table->unsignedTinyInteger('dia_semana'); // 1 = Lunes, 2 = Martes, etc.
            $table->integer('orden')->default(0);      // Secuencia de visitas en el día
            
            $table->timestamps();

            // Índice único para asegurar consistencia e impedir duplicados el mismo día
            $table->unique(['ruta_id', 'cliente_id', 'dia_semana'], 'ruta_dia_cliente_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ruta_dia_cliente');
    }
};
