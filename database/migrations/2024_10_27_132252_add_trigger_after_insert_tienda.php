<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared('
        CREATE TRIGGER after_insert_tienda
        AFTER INSERT ON tienda
        FOR EACH ROW
        BEGIN
            INSERT INTO metodopago (Nombre, MetodoPago, created_at, updated_at, fkTienda) VALUES ("Efectivo al momento del cierre", "CEF", NOW(), NOW(), NEW.idTienda);
            INSERT INTO metodopago (Nombre, MetodoPago, created_at, updated_at, fkTienda) VALUES ("Pagos mediante el resto de medios habilitados", "VO", NOW(), NOW(), NEW.idTienda);
            INSERT INTO metodopago (Nombre, MetodoPago, created_at, updated_at, fkTienda) VALUES ("Descuentos", "D", NOW(), NOW(), NEW.idTienda);
            INSERT INTO metodopago (Nombre, MetodoPago, created_at, updated_at, fkTienda) VALUES ("Ventas a crédito", "CC", NOW(), NOW(), NEW.idTienda);
            INSERT INTO metodopago (Nombre, MetodoPago, created_at, updated_at, fkTienda) VALUES ("Salida de dinero para otras partidas de la empresa", "OG", NOW(), NOW(), NEW.idTienda);
            INSERT INTO metodopago (Nombre, MetodoPago, created_at, updated_at, fkTienda) VALUES ("Saldo inicial de la caja", "CEI", NOW(), NOW(), NEW.idTienda);
        END
    ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS after_insert_tienda');
    }
};
