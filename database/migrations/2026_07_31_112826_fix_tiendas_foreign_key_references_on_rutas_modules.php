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
        // 1. Apagamos las llaves foráneas para que MySQL nos permita trabajar sin bloqueos
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // 2. Eliminamos los candados viejos que buscan la columna equivocada 'id'
        try {
            DB::statement('ALTER TABLE rutas DROP FOREIGN KEY rutas_fktienda_foreign;');
        } catch (\Exception $e) { /* Evitar caídas si no se creó */ }

        try {
            DB::statement('ALTER TABLE ruta_dia_cliente DROP FOREIGN KEY ruta_dia_cliente_fktienda_foreign;');
        } catch (\Exception $e) { /* Evitar caídas si no se creó */ }

        // 3. Re-enlazamos apuntando a la columna primaria correcta: 'idTienda'
        DB::statement('ALTER TABLE rutas ADD CONSTRAINT rutas_fktienda_foreign FOREIGN KEY (fkTienda) REFERENCES tienda(idTienda) ON DELETE CASCADE;');
        DB::statement('ALTER TABLE ruta_dia_cliente ADD CONSTRAINT ruta_dia_cliente_fktienda_foreign FOREIGN KEY (fkTienda) REFERENCES tienda(idTienda) ON DELETE CASCADE;');


        // 5. Volvemos a encender las revisiones de consistencia de MySQL
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::statement('ALTER TABLE rutas DROP FOREIGN KEY rutas_fktienda_foreign;');
        DB::statement('ALTER TABLE ruta_dia_cliente DROP FOREIGN KEY ruta_dia_cliente_fktienda_foreign;');
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
};
