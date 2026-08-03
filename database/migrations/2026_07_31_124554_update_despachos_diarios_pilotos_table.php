<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Apagamos las revisiones estrictas para evitar conflictos de enteros
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // 2. Nos aseguramos de borrar la tabla vieja si se quedó a medias
        Schema::dropIfExists('despachos_diarios_pilotos');

        // 3. Crear la estructura limpia desde cero
        Schema::create('despachos_diarios_pilotos', function (Blueprint $table) {
            $table->id();
            $table->date('fecha_despacho');
            
            $table->unsignedBigInteger('fkTienda');
            $table->string('centro_costos', 50);
            
            $table->foreignId('ruta_id')->constrained('rutas')->onDelete('cascade');
            $table->foreignId('cliente_id')->constrained('clientes')->onDelete('cascade');
            
            $table->integer('orden_visita')->default(0);
            $table->string('estatus_entrega', 30)->default('PENDIENTE');
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->index(['fecha_despacho', 'centro_costos'], 'busqueda_viaje_piloto');
        });

        // 4. CORRECCIÓN CLAVE: Apuntar a la tabla 'tienda' en singular y a la columna 'idTienda'
        DB::statement('ALTER TABLE despachos_diarios_pilotos ADD CONSTRAINT despachos_diarios_pilotos_fktienda_foreign FOREIGN KEY (fkTienda) REFERENCES tienda(idTienda) ON DELETE CASCADE;');

        // 5. Volvemos a encender las revisiones de consistencia
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Schema::dropIfExists('despachos_diarios_pilotos');
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
};
