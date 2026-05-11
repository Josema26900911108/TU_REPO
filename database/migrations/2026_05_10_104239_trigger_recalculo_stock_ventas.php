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
CREATE OR REPLACE TRIGGER tr_recalculo_stock_ventas
AFTER INSERT ON producto_venta
FOR EACH ROW
BEGIN
    DECLARE total_compras INT DEFAULT 0;
    DECLARE total_ventas INT DEFAULT 0;

    -- 1. Sumar histórico de compras (Estado 2)
    -- Aquí usamos el nombre real de tu tabla: compra_producto
    SELECT IFNULL(SUM(cp.cantidad), 0) INTO total_compras
    FROM compra_producto cp
    INNER JOIN compras c ON cp.compra_id = c.id
    WHERE cp.producto_id = NEW.producto_id 
      AND c.fkTienda = NEW.fkTienda 
      AND c.estado = 2;

    -- 2. Sumar histórico de ventas (Estado 2)
    SELECT IFNULL(SUM(pv.cantidad), 0) INTO total_ventas
    FROM producto_venta pv
    INNER JOIN ventas v ON pv.venta_id = v.id
    WHERE pv.producto_id = NEW.producto_id 
      AND v.fkTienda = NEW.fkTienda 
      AND v.estado = 2;

    -- 3. Actualizar el stock real en la tabla productos
    UPDATE productos 
    SET stock = (total_compras - total_ventas)
    WHERE id = NEW.producto_id 
      AND fkTienda = NEW.fkTienda;
END 
    ");
}

};
