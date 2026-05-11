<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up()
    {
        DB::unprepared("
            CREATE TRIGGER tr_compras_anulacion_stock
            AFTER UPDATE ON compras
            FOR EACH ROW
            BEGIN
                IF NEW.estado = 0 AND OLD.estado = 1 THEN
                    UPDATE productos p
                    INNER JOIN producto_compra pc ON p.id = pc.producto_id
                    SET p.stock = p.stock - pc.cantidad
                    WHERE pc.compra_id = NEW.id 
                    AND p.fkTienda = NEW.fkTienda;
                END IF;
            END
        ");
    }

    public function down()
    {
        DB::unprepared("DROP TRIGGER IF EXISTS tr_compras_anulacion_stock");
    }
};
