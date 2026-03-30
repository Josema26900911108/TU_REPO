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
    protected $clasesEntrada = ['101', '501', '561', '653', '642', '252'];
    protected $clasesSalida  = ['601', '201', '301', '641', '221', '251'];

    public function creating(MovimientoMateriales $movimiento)
    {
        $producto = Producto::find($movimiento->fkMateriales);
        if (!$producto) throw new \Exception("Error SAP: Material no existe.");

        // VALIDACIÓN DE STOCK PARA SALIDAS
        if (in_array($movimiento->clase_movimiento, $this->clasesSalida)) {
            if ($producto->perecedero == 1) {
                $lote = Lotesalarma::find($movimiento->fkLotes);
                if (!$lote || $lote->cantidad < $movimiento->cantidad) {
                    throw new \Exception("Error SAP: Stock insuficiente en Lote.");
                }
            } else {
                if ($producto->stock < $movimiento->cantidad) {
                    throw new \Exception("Error SAP: Stock insuficiente general para {$producto->nombre}.");
                }
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
        // Buscamos si el producto vendido tiene una receta (ingredientes)
        $ingredientes = Receta::where('producto_padre_id', $producto->id)->get();

        foreach ($ingredientes as $item) {
            $cantidadTotalADescontar = $item->cantidad * $movimientoPadre->cantidad;

            // Creamos un movimiento 261 (Consumo de producción) por cada ingrediente
            // Esto disparará este mismo Observer para descontar el stock de los insumos
            MovimientoMateriales::create([
                'fkTienda' => $movimientoPadre->fkTienda,
                'fkMateriales' => $item->ingrediente_id,
                'clase_movimiento' => '261', 
                'tipo_movimiento' => 'SALIDA_RECETA',
                'origen_uso' => 'consumo_produccion',
                'cantidad' => $cantidadTotalADescontar,
                'documento_material' => $movimientoPadre->documento_material,
                'referencia' => "Insumo de: {$producto->nombre}",
                'fecha_contabilizacion' => now(),
                'centro' => $movimientoPadre->centro,
                'almacen' => $movimientoPadre->almacen,
                'unidad_medida_base' => $item->unidad_medida ?? 'PZA'
            ]);
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
        if ($movimiento->created_at->diffInHours(now()) > 24 && (!$usuario || !$usuario->hasRole('root'))) {
            throw new \Exception("Acceso Denegado: Solo el usuario Root puede anular movimientos antiguos.");
        }
    }
}
