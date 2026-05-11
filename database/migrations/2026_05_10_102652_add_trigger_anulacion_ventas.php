<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up()
    {
        DB::unprepared("
            CREATE TRIGGER tr_ventas_anulacion_stock
            AFTER UPDATE ON ventas
            FOR EACH ROW
            BEGIN
                IF NEW.estado = 0 AND OLD.estado = 1 THEN
                    UPDATE productos p
                    INNER JOIN producto_venta pv ON p.id = pv.producto_id
                    SET p.stock = p.stock + pv.cantidad
                    WHERE pv.venta_id = NEW.id 
                    AND p.fkTienda = NEW.fkTienda;
                END IF;
            END
        ");
    }

    public function down()
    {
        DB::unprepared("DROP TRIGGER IF EXISTS tr_ventas_anulacion_stock");
    }
};
