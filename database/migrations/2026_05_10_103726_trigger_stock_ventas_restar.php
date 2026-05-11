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
        CREATE TRIGGER tr_stock_ventas_calculo
        AFTER INSERT ON producto_venta
        FOR EACH ROW
        BEGIN
            -- Resta la cantidad vendida al stock de la tienda
            UPDATE productos 
            SET stock = stock - NEW.cantidad
            WHERE id = NEW.producto_id 
            AND fkTienda = NEW.fkTienda;
        END
    ");
}

};
