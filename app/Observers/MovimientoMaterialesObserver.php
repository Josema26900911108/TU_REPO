<?php

namespace App\Observers;

use App\Models\MovimientoMateriales;
use App\Models\Producto;
use App\Models\Lotesalarma;
use App\Models\User;
use App\Notifications\StockInsuficienteNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Exception;
use Illuminate\Support\Facades\Log;


class MovimientoMaterialesObserver
{
protected $clasesEntrada = ['101', '501', '561', '653', '642', '252'];
protected $clasesSalida  = ['601', '201', '301', '641', '221', '251'];

// App/Observers/MovimientoMaterialesObserver.php

public function creating(MovimientoMateriales $movimiento)
{
    $admins = User::whereHas('roles', function($q){
    $q->where('name','like','%root');
})->get();

    // 1. VALIDACIÓN GENERAL DE STOCK (Para todas las salidas: 601, 251, 641, 301, etc.)
    if (in_array($movimiento->clase_movimiento, $this->clasesSalida)) {
        $lote = Lotesalarma::find($movimiento->fkLotes);

        if (!$lote || $lote->cantidad < $movimiento->cantidad) {
            $stockDisponible = $lote ? $lote->cantidad : 0;
            Notification::send($admins, new StockInsuficienteNotification($movimiento, auth()->user()));
            throw new \Exception("Error SAP: Stock insuficiente en Lote para movimiento {$movimiento->clase_movimiento}. Disponible: {$stockDisponible}.");
        }
    }

    // 2. VALIDACIÓN DE TRASLADOS (301 y 311)
    if (in_array($movimiento->clase_movimiento, ['301', '311'])) {
        if (empty($movimiento->almacen_destino)) {
            Notification::send($admins, new StockInsuficienteNotification($movimiento, auth()->user()));
            throw new \Exception("Error SAP: Los traslados (301/311) requieren un 'Almacén Destino' obligatorio.");
        }
    }

    // 3. VALIDACIÓN DE TÉCNICOS (251)
    if ($movimiento->clase_movimiento == '251') {
        if (empty($movimiento->contrata)) {
            Notification::send($admins, new StockInsuficienteNotification($movimiento, auth()->user()));
            throw new \Exception("Error SAP: El movimiento 251 requiere especificar el Técnico responsable (campo contrata).");
        }
    }
}



public function created(MovimientoMateriales $movimiento)
{
    $producto = Producto::find($movimiento->fkMateriales);
    $cantidad = $movimiento->cantidad;
        $admins = User::whereHas('roles', function($q){
    $q->where('name','like','%root');
})->get();
    // 1. VALIDACIÓN DE SEGURIDAD (Solo para salidas)
    if (in_array($movimiento->clase_movimiento, $this->clasesSalida)) {
        // Validamos contra el Lote específico si existe, o contra el stock general
        $lote = Lotesalarma::find($movimiento->fkLotes);
        $stockDisponible = $lote ? $lote->cantidad : $producto->stock;

        if ($stockDisponible < $cantidad) {
            Notification::send($admins, new StockInsuficienteNotification($movimiento, auth()->user()));
            throw new \Exception("Error SAP: Stock insuficiente en Lote/Producto para movimiento {$movimiento->clase_movimiento}.");
        }
    }

    // 2. AJUSTE AUTOMÁTICO (Llama a tu función centralizada)
    $this->ajustarStock($movimiento, 'normal');
}

    /**
     * Handle the MovimientoMateriales "updated" event.
     */
    public function updated(MovimientoMateriales $movimientoMateriales): void
    {
        //
    }

 protected function ajustarStock($movimiento, $modo)
{
    $esEntrada = in_array($movimiento->clase_movimiento, $this->clasesEntrada);
    $cantidad = $movimiento->cantidad;

    // Lógica Booleana:
    // Si es normal y entrada -> SUMA
    // Si es reversa y entrada -> RESTA
    // Si es normal y salida -> RESTA
    // Si es reversa y salida -> SUMA
    $debeSumar = ($modo === 'normal') ? $esEntrada : !$esEntrada;

    if ($debeSumar) {
        Producto::where('id', $movimiento->fkMateriales)->increment('stock', $cantidad);
        if ($movimiento->fkLotes) {
            Lotesalarma::where('id', $movimiento->fkLotes)->increment('cantidad', $cantidad);
        }
    } else {
        Producto::where('id', $movimiento->fkMateriales)->decrement('stock', $cantidad);
        if ($movimiento->fkLotes) {
            Lotesalarma::where('id', $movimiento->fkLotes)->decrement('cantidad', $cantidad);
        }
    }
}

    /**
     * Handle the MovimientoMateriales "deleted" event.
     */
    public function deleted(MovimientoMateriales $movimientoMateriales): void
    {
         $this->ajustarStock($movimientoMateriales, 'reversa');
    }

public function deleting(MovimientoMateriales $movimiento)
{

        $admins = User::whereHas('roles', function($q){
    $q->where('name','like','%root');
})->get();

    /** @var \App\Models\User $usuario */
    $usuario = auth()->user();
    $ahora = now();

    // Si pasaron más de 24 horas y NO es el administrador
    if ($movimiento->created_at->diffInHours($ahora) > 24 && !$usuario->isAdmin()) {
        Notification::send($admins, new StockInsuficienteNotification($movimiento, auth()->user()));
        throw new \Exception("Acceso Denegado: Solo el usuario Root puede anular movimientos antiguos.");
    }
}



    /**
     * Handle the MovimientoMateriales "restored" event.
     */
    public function restored(MovimientoMateriales $movimientoMateriales): void
    {
        //
    }

    /**
     * Handle the MovimientoMateriales "force deleted" event.
     */
    public function forceDeleted(MovimientoMateriales $movimientoMateriales): void
    {
        //
    }
}
