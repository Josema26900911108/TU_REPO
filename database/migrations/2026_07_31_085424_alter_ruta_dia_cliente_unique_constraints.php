<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Forzamos la desactivación temporal de las restricciones de llaves foráneas en MySQL
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Eliminamos de forma directa el índice único anterior
        DB::statement('ALTER TABLE ruta_dia_cliente DROP INDEX ruta_dia_cliente_unique;');

        // Creamos el nuevo índice compuesto por las 3 columnas indispensables para el multi-día
        DB::statement('ALTER TABLE ruta_dia_cliente ADD UNIQUE KEY ruta_dia_cliente_unique (ruta_id, cliente_id, dia_semana);');

        // Volvemos a activar las restricciones de llaves foráneas de forma segura
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::statement('ALTER TABLE ruta_dia_cliente DROP INDEX ruta_dia_cliente_unique;');
        DB::statement('ALTER TABLE ruta_dia_cliente ADD UNIQUE KEY ruta_dia_cliente_unique (ruta_id, cliente_id);');
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
};
