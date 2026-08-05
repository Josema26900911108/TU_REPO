<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class PilotoDespachoController extends Controller
{
    /**
     * Listar la hoja de ruta del día de hoy asignada al Piloto
     */
public function miHojaDeRuta(Request $request)
{
    // 1. Validación estricta de sesión de usuario activa
    if (!Auth::check()) {
        return redirect()->route('login');
    }

    $userId = Auth::id();
    $fkTiendaUsuario = session('user_fkTienda');
    $hoy = Carbon::today()->format('Y-m-d');

    // 2. BUSCAR SI EL USUARIO ACTIVO TIENE UN PERFIL DE TÉCNICO/CONDUCTOR ASIGNADO
    $tecnico = DB::table('tecnico')
        ->where('fkuser', $userId)
        ->where('fkTienda', $fkTiendaUsuario)
        ->first();

    // Si no tiene perfil de técnico o no está dado de alta en esta tienda
    if (!$tecnico) {
        return view('pilotos.depacho', [
            'visitas' => [], 
            'hoy' => $hoy,
            'centroCostosPiloto' => null,
            'message' => 'Tu usuario no cuenta con un perfil de Técnico/Conductor asignado a esta sucursal.'
        ]);
    }

    // El código del técnico es el que define contablemente su ruta/camión
    $centroCostosPiloto = $tecnico->codigo; 

    // 3. Consultar las visitas asignadas a su código/centro de costos para el día de hoy
$visitas = DB::table('clientes')
    ->join('personas', 'clientes.persona_id', '=', 'personas.id')
    ->leftJoin('despachos_diarios_pilotos', function($join) use ($hoy, $centroCostosPiloto, $fkTiendaUsuario) {
        $join->on('clientes.id', '=', 'despachos_diarios_pilotos.cliente_id')
             ->where('despachos_diarios_pilotos.fecha_despacho', $hoy)
             ->where('despachos_diarios_pilotos.centro_costos', $centroCostosPiloto)
             ->where('despachos_diarios_pilotos.fkTienda', $fkTiendaUsuario);
    })
    ->leftJoin('rutas', 'despachos_diarios_pilotos.ruta_id', '=', 'rutas.id')
    ->select(
        'clientes.id as id',
        // Traemos todas las columnas excepto estatus_entrega para evitar que se duplique
        'despachos_diarios_pilotos.id as despacho_id',
        'despachos_diarios_pilotos.fecha_despacho',
        'despachos_diarios_pilotos.fkTienda',
        'despachos_diarios_pilotos.centro_costos',
        'despachos_diarios_pilotos.ruta_id',
        'despachos_diarios_pilotos.cliente_id',
        'despachos_diarios_pilotos.orden_visita',
        'despachos_diarios_pilotos.observaciones',
        'despachos_diarios_pilotos.created_at',
        'despachos_diarios_pilotos.updated_at',
        
        'personas.razon_social as cliente_nombre',
        'personas.direccion as cliente_direccion',
        'personas.numero_documento as cliente_nit',
        
        // Si el estatus de entrega es NULL o dice 'rechazado', lo forzamos al texto que necesitas
        DB::raw("CASE 
            WHEN despachos_diarios_pilotos.estatus_entrega IS NULL OR despachos_diarios_pilotos.estatus_entrega = 'rechazado' THEN 'sin pedidos a despachar' 
            ELSE despachos_diarios_pilotos.estatus_entrega 
        END as estatus_entrega"),
        
        // Controlamos también el nombre de la ruta por si acaso
        DB::raw("COALESCE(rutas.nombre, 'sin pedidos a despachar') as nombre_ruta")
    )
    ->orderByRaw('ISNULL(despachos_diarios_pilotos.orden_visita), despachos_diarios_pilotos.orden_visita asc')
    ->get();



    // 4. Retornar a la vista corregida (despacho en plural) con su compact completo
    return view('pilotos.depacho', compact('visitas', 'hoy', 'centroCostosPiloto', 'tecnico'));
}



    /**
     * Actualizar el estatus de la entrega desde el teléfono o la computadora del piloto
     */

    // 1. HISTORIAL DE COMPRAS
public function historialCompras($clienteId)
{
    try {
        // NOTA: Revisa si tu tabla se llama 'pedidos', 'ventas' o 'facturas'
        $historial = DB::table('pedidos') 
            ->select('id', 'fecha', 'total', 'estado') // Verifica si estas columnas existen
            ->where('cliente_id', $clienteId)
            ->orderBy('fecha', 'desc')
            ->limit(20)
            ->get();

        return response()->json($historial);

    } catch (Exception $e) {
        // Si la consulta falla, envía el mensaje de error real a la consola de JS
        return response()->json([
            'error' => true,
            'mensaje' => $e->getMessage()
        ], 500);
    }
}

// 2. TOP 10 PRODUCTOS MÁS VENDIDOS
public function topProductos($clienteId)

{
    $topProductos = DB::table('pedido_detalles') // Tu tabla pivote/detalle de venta
        ->join('pedidos', 'pedido_detalles.pedido_id', '=', 'pedidos.id')
        ->join('productos', 'pedido_detalles.producto_id', '=', 'productos.id')
        ->select(
            'productos.nombre as producto',
            DB::raw('SUM(pedido_detalles.cantidad) as total_cantidad'),
            DB::raw('COUNT(pedido_detalles.id) as veces_comprado')
        )
        ->where('pedidos.cliente_id', $clienteId)
        // Opcional: ->where('pedidos.estado', 'completado') 
        ->groupBy('productos.id', 'productos.nombre')
        ->orderBy('total_cantidad', 'desc')
        ->limit(10)
        ->get();

    return response()->json($topProductos);
}

    public function actualizarEstatusEntrega(Request $request, $id)
    {
        $request->validate([
            'estatus' => 'required|in:ENTREGADO,RECHAZADO,PENDIENTE',
            'observaciones' => 'nullable|string|max:500'
        ]);

        DB::table('despachos_diarios_pilotos')
            ->where('id', $id)
            ->update([
                'estatus_entrega' => $request->estatus,
                'observaciones' => $request->observaciones,
                'updated_at' => now()
            ]);

        return redirect()->back()->with('success', 'Estatus de la entrega actualizado en tus costos.');
    }

    
}
