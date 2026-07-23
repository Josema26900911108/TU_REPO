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
            $table->id(); // Convención moderna de Laravel (BigIncrements implícito)
            
            // Relación externa limpia y tipada correctamente
            $table->foreignId('fkTienda')
                  ->nullable()
                  ->constrained('tienda')
                  ->onDelete('cascade');
            
            // Campos indexados para búsquedas de alta velocidad
            $table->string('serie')->nullable()->index(); 
            $table->string('SKU')->nullable()->index();   
            $table->string('Lote')->nullable()->index();  
            $table->string('MAC1')->nullable()->index();  
            $table->string('CENTRO')->nullable()->index(); 

            // Campos informativos y técnicos
            $table->string('almacen')->nullable();
            $table->string('MAC2')->nullable();
            $table->string('MAC3')->nullable();
            $table->string('ESTATUS')->nullable();
            $table->decimal('COSTO', 12, 2)->nullable(); // Reemplazado double por decimal (mayor precisión financiera)
            $table->string('TIPO')->nullable();
            $table->string('unidadmedida')->nullable();
            $table->string('TIPOMOVIMIENTO')->nullable();

            // Auditoría (Se eliminaron duplicados y se usa la convención de Laravel)
            $table->string('Modificado_por')->nullable();
            $table->string('Creado_por')->nullable();
            $table->timestamps(); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('materialexistentesap');
    }
};
