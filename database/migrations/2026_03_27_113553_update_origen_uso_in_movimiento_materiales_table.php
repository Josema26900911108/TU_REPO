<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB; // Importante

return new class extends Migration
{
    public function up(): void
    {
        // Definimos la lista de valores como un string para el SQL
        $valores = "'" . implode("','", [
            'compra_nacional', 'importacion', 'devolucion_cliente', 'consignacion_recibida', 
            'donacion_recibida', 'reingreso_prestamo', 'venta_directa', 'exportacion', 
            'devolucion_proveedor', 'consignacion_enviada', 'obsequio_marketing', 
            'bonificacion_venta', 'consumo_materia_prima', 'ingreso_producto_terminado', 
            'ingreso_subproducto', 'merma_proceso', 'reproceso_fallido', 'ensamblaje_kit', 
            'desensamblaje', 'traslado_entre_bodegas', 'stock_en_transito', 
            'ajuste_inventario_positivo', 'ajuste_inventario_negativo', 'saldo_inicial', 
            'cambio_sku_etiquetado', 'reacondicionado_refurbished', 'despacho_instalacion', 
            'consumo_instalacion', 'devolucion_instalacion', 'material_dañado_obra', 
            'herramienta_en_obra', 'retiro_instalacion_vieja', 'garantia_instalacion', 
            'despacho_a_tecnico', 'retorno_de_tecnico', 'consumo_en_obra', 
            'garantia_otorgada', 'uso_herramienta_interna', 'venta_en_ruta', 
            'liquidacion_de_ruta', 'baja_por_vencimiento', 'baja_por_rotura_daño', 
            'baja_por_obsolescencia', 'perdida_por_robo', 'bloqueado_por_calidad', 
            'cuarentena_sanitaria', 'autoconsumo_personal', 'gasto_administrativo', 
            'mantenimiento_sede', 'donacion_entregada', 'conversion_a_activo_fijo', 'otros'
        ]) . "'";

        // Ejecutamos el cambio directamente
        DB::statement("ALTER TABLE movimiento_materiales MODIFY COLUMN origen_uso ENUM($valores) DEFAULT 'otros'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE movimiento_materiales MODIFY COLUMN origen_uso VARCHAR(255) DEFAULT 'otros'");
    }
};
