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
        // Usamos CREATE OR REPLACE para que se actualice si ya existe
        DB::unprepared("
CREATE OR REPLACE VIEW vista_stock_contable AS
SELECT 
    p.id AS producto_id,
    p.nombre,
    p.fkTienda,
    -- Calculamos la diferencia: Total Compras (Estado 2) - Total Ventas (Estado 2)
    (
        SELECT IFNULL(SUM(cp.cantidad), 0) 
        FROM compra_producto cp 
        INNER JOIN compras c ON cp.compra_id = c.id 
        WHERE cp.producto_id = p.id AND c.fkTienda = p.fkTienda AND c.estado = 2
    ) - (
        SELECT IFNULL(SUM(pv.cantidad), 0) 
        FROM producto_venta pv 
        INNER JOIN ventas v ON pv.venta_id = v.id 
        WHERE pv.producto_id = p.id AND v.fkTienda = p.fkTienda AND v.estado = 2
    ) AS stock_contable
FROM productos p;

        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared("DROP VIEW IF EXISTS vista_stock_contable");
    }
};
