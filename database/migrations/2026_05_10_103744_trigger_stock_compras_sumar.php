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
        CREATE TRIGGER tr_stock_compras_calculo
        AFTER INSERT ON compra_producto
        FOR EACH ROW
        BEGIN
            -- Suma la cantidad comprada al stock de la tienda
            UPDATE productos 
            SET stock = stock + NEW.cantidad
            WHERE id = NEW.producto_id 
            AND fkTienda = NEW.fkTienda;
        END
    ");
}

};
