<?php

namespace App\Observers;

use App\Models\MovimientoMateriales;
use App\Models\Producto;
use App\Models\Lotesalarma;
use App\Models\User;
use App\Models\Receta;
use App\Notifications\StockInsuficienteNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Log;

class MovimientoMaterialesObserver
{
    /**
     * Clases de movimiento que SUMAN stock físico al almacén (Entradas).
     * Se añadieron:
     * - '311' (Traspaso en 1 paso): Suma en la bodega destino.
     * - '315' (Traspaso en 2 pasos - Entrada): Confirma la recepción del tránsito.
     * - '312' (Anulación Traspaso): Devuelve stock al origen si se cancela un 311.
     * - '262' (Anulación Consumo Orden): Devuelve materiales de instalación al técnico.
     */
    protected $clasesEntrada = [
        '101', '501', '561', '653', '642', '252', '602', 
        '311', '312', '315', '262'
    ];

    /**
     * Clases de movimiento que RESTAN stock físico o extinguen inventario (Salidas).
     * Se añadieron:
     * - '311' (Traspaso en 1 paso): Resta en la bodega origen.
     * - '313' (Traspaso en 2 pasos - Salida): Resta origen y congela el lote "En Tránsito".
     * - '261' (Consumo de Orden): Salida definitiva por instalación física.
     * - '551' (Merma/Desguace): Destrucción física o baja por daño (Escenario Merma).
     */
    protected $clasesSalida  = [
        '601', '201', '301', '641', '221', '251', 
        '311', '313', '261', '551'
    ];


public function creating(MovimientoMateriales $movimiento)
{
    $producto = Producto::find($movimiento->fkMateriales);
    if (!$producto) throw new \Exception("Error: Material no existe.");

    if (in_array($movimiento->clase_movimiento, $this->clasesSalida)) {
        
        // 1. LÓGICA PARA MATERIAL SERIADO (Equipos/Instalaciones)
        if ($producto->es_seriado) { 
            // Aquí validarías que el movimiento traiga el número de serie
            // usualmente en una tabla pivote o campo 'referencia_seriado'
            if (!$movimiento->referencia_sap) throw new \Exception("Material seriado requiere Serie.");
        }

        // 2. LÓGICA PARA PERECEDEROS / LOTES (Alimentos/Químicos)
        elseif ($producto->perecedero == 1) {
            if (is_null($movimiento->fkLotes)) {
                // Selección automática FIFO si el descuento/venta no especificó uno
                $lote = Lotesalarma::where('producto_id', $producto->id)
                    ->where('cantidad', '>=', $movimiento->cantidad)
                    ->where('estado', 'disponible')
                    ->orderBy('fecha_vencimiento', 'asc')
                    ->first();

                if (!$lote) throw new \Exception("No hay lote disponible para: {$producto->nombre}");
                $movimiento->fkLotes = $lote->id;
            }
        }

        // 3. LÓGICA PARA MISCELÁNEOS (Stock General)
        // Se valida contra el stock general del producto sin importar lotes o series
        if ($producto->stock < $movimiento->cantidad) {
            throw new \Exception("Stock insuficiente general para {$producto->nombre}.");
        }
    }
}



    public function created(MovimientoMateriales $movimiento)
    {
        $producto = Producto::find($movimiento->fkMateriales);
        if (!$producto) return;

        // 1. AJUSTE DE STOCK FÍSICO (Lote o General)
        if (in_array($movimiento->clase_movimiento, $this->clasesSalida)) {
            if ($producto->perecedero == 1 && $movimiento->fkLotes) {
                Lotesalarma::where('id', $movimiento->fkLotes)->decrement('cantidad', $movimiento->cantidad);
            }
            $producto->decrement('stock', $movimiento->cantidad);
        } elseif (in_array($movimiento->clase_movimiento, $this->clasesEntrada)) {
            if ($producto->perecedero == 1 && $movimiento->fkLotes) {
                Lotesalarma::where('id', $movimiento->fkLotes)->increment('cantidad', $movimiento->cantidad);
            }
            $producto->increment('stock', $movimiento->cantidad);
        }

        // 2. EXPLOSIÓN DE RECETA (Si es una Venta 601 y tiene receta)
        if ($movimiento->clase_movimiento == '601') {
            $this->procesarConsumoReceta($producto, $movimiento);
        }
    }

    /**
     * Lógica para descontar ingredientes automáticamente
     */
private function procesarConsumoReceta(Producto $producto, MovimientoMateriales $movimientoPadre)
{
    $ingredientes = Receta::where('producto_padre_id', $producto->id)->get();

    foreach ($ingredientes as $item) {
        $cantidadTotalADescontar = $item->cantidad * $movimientoPadre->cantidad;
        $insumo = Producto::find($item->ingrediente_id);

        if (!$insumo) continue;

        if ($insumo->perecedero == 1) {
            // Lógica de Lotes (FIFO)
            $lotes = Lotesalarma::where('producto_id', $insumo->id)
                ->where('cantidad', '>', 0)
                ->where('estado', 'disponible')
                ->orderBy('fecha_vencimiento', 'asc')
                ->get();

            foreach ($lotes as $lote) {
                if ($cantidadTotalADescontar <= 0) break;

                $cantidadATomar = min($lote->cantidad, $cantidadTotalADescontar);

                MovimientoMateriales::create([
                    'fkTienda' => $movimientoPadre->fkTienda,
                    'fkMateriales' => $insumo->id,
                    'fkLotes' => $lote->id,
                    'clase_movimiento' => '261', 
                    'tipo_movimiento' => 'SALIDA_RECETA',
                    'origen_uso' => 'consumo_produccion',
                    'cantidad' => $cantidadATomar,
                    'documento_material' => $movimientoPadre->documento_material,
                    'referencia' => "Insumo (Lote) de: ||{$producto->nombre}||",
                    'fecha_contabilizacion' => now(),
                    'centro' => $movimientoPadre->centro,
                    'almacen' => $movimientoPadre->almacen,
                    'unidad_medida_base' => $insumo->unidad_medida ?? 'PZA'
                ]);

                $cantidadTotalADescontar -= $cantidadATomar;
            }
        } else {
            // Movimiento simple (Misceláneos o Seriados sin lote)
            MovimientoMateriales::create([
                'fkTienda' => $movimientoPadre->fkTienda,
                'fkMateriales' => $insumo->id,
                'clase_movimiento' => '261', 
                'tipo_movimiento' => 'SALIDA_RECETA',
                'origen_uso' => 'consumo_produccion',
                'cantidad' => $cantidadTotalADescontar,
                'documento_material' => $movimientoPadre->documento_material,
                'referencia' => "Insumo de: ||{$producto->nombre}||",
                'fecha_contabilizacion' => now(),
                'centro' => $movimientoPadre->centro,
                'almacen' => $movimientoPadre->almacen,
                'unidad_medida_base' => $insumo->unidad_medida ?? 'PZA'
            ]);
        }
    }
}


    public function deleted(MovimientoMateriales $movimiento)
    {
        $this->ajustarStock($movimiento, 'reversa');
    }

    protected function ajustarStock($movimiento, $modo)
    {
        $esEntrada = in_array($movimiento->clase_movimiento, $this->clasesEntrada);
        $debeSumar = ($modo === 'normal') ? $esEntrada : !$esEntrada;

        $producto = Producto::find($movimiento->fkMateriales);
        if (!$producto) return;

        if ($debeSumar) {
            $producto->increment('stock', $movimiento->cantidad);
            if ($movimiento->fkLotes) Lotesalarma::where('id', $movimiento->fkLotes)->increment('cantidad', $movimiento->cantidad);
        } else {
            $producto->decrement('stock', $movimiento->cantidad);
            if ($movimiento->fkLotes) Lotesalarma::where('id', $movimiento->fkLotes)->decrement('cantidad', $movimiento->cantidad);
        }
    }

    public function deleting(MovimientoMateriales $movimiento)
    {
        $usuario = auth()->user();
        if ($movimiento->created_at->diffInHours(now()) > 24 && (!$usuario || $usuario->role != 'root')) {
            throw new \Exception("Acceso Denegado: Solo el usuario Root puede anular movimientos antiguos.");
        }
    }
}
