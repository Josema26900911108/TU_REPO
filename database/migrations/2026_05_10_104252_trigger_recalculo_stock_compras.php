<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
public function up()
{
    DB::unprepared("
        CREATE TRIGGER tr_recalculo_stock_compras
        AFTER INSERT ON compra_producto
        FOR EACH ROW
        BEGIN
            DECLARE total_compras INT DEFAULT 0;
            DECLARE total_ventas INT DEFAULT 0;

            -- Lo mismo: sumar histórico de compras (estatus 2)
            SELECT IFNULL(SUM(pc.cantidad), 0) INTO total_compras
            FROM compra_producto pc
            INNER JOIN compras c ON pc.compra_id = c.id
            WHERE pc.producto_id = NEW.producto_id 
              AND pc.fkTienda = NEW.fkTienda 
              AND c.estado = 2;

            -- Sumar histórico de ventas (estatus 2)
            SELECT IFNULL(SUM(pv.cantidad), 0) INTO total_ventas
            FROM producto_venta pv
            INNER JOIN ventas v ON pv.venta_id = v.id
            WHERE pv.producto_id = NEW.producto_id 
              AND pv.fkTienda = NEW.fkTienda 
              AND v.estado = 2;

            -- Actualizar balance real
            UPDATE productos 
            SET stock = (total_compras - total_ventas)
            WHERE id = NEW.producto_id 
              AND fkTienda = NEW.fkTienda;
        END
    ");
}

};
