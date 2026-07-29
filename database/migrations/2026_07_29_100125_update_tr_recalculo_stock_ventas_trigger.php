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
        // 1. Eliminar triggers anteriores si existen para evitar conflictos
        DB::unprepared('DROP TRIGGER IF EXISTS tr_stock_ventas_calculo;');
        DB::unprepared('DROP TRIGGER IF EXISTS tr_recalculo_stock_ventas;');

        // 2. Crear el nuevo trigger seguro con protección contra valores negativos
        DB::unprepared("
            CREATE TRIGGER tr_recalculo_stock_ventas
            AFTER INSERT ON producto_venta
            FOR EACH ROW
            BEGIN
                DECLARE total_compras INT DEFAULT 0;
                DECLARE total_ventas INT DEFAULT 0;

                -- Sumar histórico de compras aprobadas (Estado 2)
                SELECT IFNULL(SUM(cp.cantidad), 0) INTO total_compras
                FROM compra_producto cp
                INNER JOIN compras c ON cp.compra_id = c.id
                WHERE cp.producto_id = NEW.producto_id 
                  AND c.fkTienda = NEW.fkTienda 
                  AND c.estado = 2;

                -- Sumar histórico de ventas aprobadas (Estado 2)
                SELECT IFNULL(SUM(pv.cantidad), 0) INTO total_ventas
                FROM producto_venta pv
                INNER JOIN ventas v ON pv.venta_id = v.id
                WHERE pv.producto_id = NEW.producto_id 
                  AND v.fkTienda = NEW.fkTienda 
                  AND v.estado = 2;

                -- Actualizar el stock asegurando que nunca sea menor a cero
                UPDATE productos 
                SET stock = GREATEST(0, total_compras - total_ventas)
                WHERE id = NEW.producto_id 
                  AND fkTienda = NEW.fkTienda;
            END
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // En caso de rollback, eliminamos el trigger creado
        DB::unprepared('DROP TRIGGER IF EXISTS tr_recalculo_stock_ventas;');
    }
};
