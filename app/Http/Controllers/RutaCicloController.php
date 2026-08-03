<?php

namespace App\Http\Controllers;

use App\Models\Ruta;
use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RutaCicloController extends Controller
{

public function index(Request $request)
{
    // Capturar la tienda del usuario en sesión
    $fkTiendaUsuario = session('user_fkTienda');

    // Obtener las rutas que pertenecen únicamente a la tienda del usuario
    $rutas = DB::table('rutas')->where('fkTienda', $fkTiendaUsuario)->get();

    $rutaSeleccionadaId = $request->input('ruta_id', $rutas->first()?->id);
    
    $rutaActual = null;
    $clientesPorDia = [];
    $totalDiasCiclo = 7;

    if ($rutaSeleccionadaId) {
        $rutaActual = \App\Models\Ruta::with(['clientes.persona'])
            ->where('fkTienda', $fkTiendaUsuario)
            ->find($rutaSeleccionadaId);
        
        if ($rutaActual) {
// Dentro de tu método index(), donde calculas $totalDiasCiclo:
if ($rutaActual->tipo_ciclo === 'fin_de_semana') { 
    $totalDiasCiclo = 2; 
} elseif ($rutaActual->tipo_ciclo === 'quincenal') { 
    $totalDiasCiclo = 14; 
} elseif ($rutaActual->tipo_ciclo === 'mensual' || $rutaActual->tipo_ciclo === 'personalizado') { 
    $totalDiasCiclo = 30; // Tanto el mensual como el personalizado despliegan 30 días para libre distribución
} else {
    $totalDiasCiclo = 7; 
}


            $clientesPorDia = array_fill(1, $totalDiasCiclo, []);

            foreach ($rutaActual->clientes as $cliente) {
                $diaAsignado = $cliente->pivot->dia_semana;
                if ($rutaActual->tipo_ciclo === 'fin_de_semana') {
                    if ($diaAsignado == 6) $diaAsignado = 1;
                    if ($diaAsignado == 7) $diaAsignado = 2;
                }
                if ($diaAsignado <= $totalDiasCiclo) {
                    $clientesPorDia[$diaAsignado][] = $cliente;
                }
            }
            
            foreach ($clientesPorDia as $dia => $clientes) {
                usort($clientesPorDia[$dia], function($a, $b) { return $a->pivot->orden <=> $b->pivot->orden; });
            }
        }
    }

    // Bolsa de clientes disponibles filtrada por la tienda
    $todosLosClientes = $rutaActual 
        ? \App\Models\Cliente::with('persona')
            ->whereHas('persona', function($q) use ($fkTiendaUsuario) {
                // Si tus clientes se segmentan por tienda a nivel de persona o cliente, añade su filtro aquí
            })
            ->whereDoesntHave('rutas', function($q) use ($rutaSeleccionadaId) {
                $q->where('rutas.id', $rutaSeleccionadaId);
            })->get()
        : \App\Models\Cliente::with('persona')->get();

    // OBTENER LOS CENTROS DE COSTOS EXCLUSIVOS DE LA TIENDA EN SESIÓN
    $centrosCostos = DB::table('centro')
        ->where('fkTienda', $fkTiendaUsuario)
        ->get();

    return view('rutas_ciclos.index', compact('rutas', 'rutaActual', 'clientesPorDia', 'todosLosClientes', 'totalDiasCiclo', 'centrosCostos'));
}

public function storeRuta(Request $request)
{
    $request->validate([
        'nombre' => 'required|string|max:255',
        'tipo_ciclo' => 'required|string|max:50',
        'fkCentro' => 'required|exists:centro,id', // Validamos el ID del centro seleccionado
    ]);

    // Obtener el código o nombre del centro seleccionado
    $centro = DB::table('centro')->find($request->fkCentro);
    $fkTiendaUsuario = session('user_fkTienda'); // Forzar la tienda de la sesión

    $rutaId = DB::table('rutas')->insertGetId([
        'nombre' => $request->nombre,
        'tipo_ciclo' => $request->tipo_ciclo,
        'fkTienda' => $fkTiendaUsuario, // Guardado automático por sesión
        'centro_costos' => $centro ? $centro->codigo : null, // Guardamos el código correlativo contable
        'created_at' => now(),
        'updated_at' => now()
    ]);

    return redirect()->route('rutas.index', ['ruta_id' => $rutaId])
                     ->with('success', 'Ruta cíclica configurada para tu sucursal.');
}

public function actualizarCentroCostos(Request $request)
{
    $request->validate([
        'ruta_id' => 'required|integer|exists:rutas,id',
        'centro_costos_codigo' => 'nullable|string|max:255' // Cambiado a nullable
    ]);

    $rutaId = (int) $request->ruta_id;
    
    // Si el valor viene vacío o es un string vacío, lo transformamos en NULL real para la DB
    $codigoCC = !empty($request->centro_costos_codigo) ? trim($request->centro_costos_codigo) : null;

    DB::transaction(function () use ($rutaId, $codigoCC) {
        
        // 1. Desvincular en la tabla de rutas principal (Queda en NULL)
        DB::table('rutas')
            ->where('id', $rutaId)
            ->update([
                'centro_costos' => $codigoCC,
                'updated_at' => now()
            ]);

        // 2. Desvincular en cascada a todos los clientes de esa ruta (Quedan en NULL)
        DB::table('ruta_dia_cliente')
            ->where('ruta_id', $rutaId)
            ->update([
                'centro_costos' => $codigoCC,
                'updated_at' => now()
            ]);
    });

    return response()->json([
        'status' => 'success',
        'message' => $codigoCC 
            ? 'Centro de costos actualizado correctamente.' 
            : 'Ruta desvinculada exitosamente (Quedó Sin Asignar).'
    ]);
}


public function eliminarCliente(Request $request)
{
    $request->validate([
        'cliente_id' => 'required|exists:clientes,id',
        'ruta_id' => 'required|exists:rutas,id',
        'dia_semana' => 'required|integer|between:1,7',
    ]);

    // Elimina únicamente el registro que coincida con el cliente, ruta y día específico
DB::table('ruta_dia_cliente')
    ->where('ruta_id', $request->ruta_id)
    ->where('cliente_id', $request->cliente_id)
    ->where('dia_semana', $request->dia_semana)
    ->delete();

// --- SISTEMA DE REORGANIZACIÓN EN CASCADA AUTOMÁTICO ---
// Leemos los vecinos que sobrevivieron en la columna de ese día específico
$vecinosRestantes = DB::table('ruta_dia_cliente')
    ->where('ruta_id', $request->ruta_id)
    ->where('dia_semana', $request->dia_semana)
    ->orderBy('orden', 'asc')
    ->get();

// Reescribimos sus posiciones de forma correlativa estricta: 1, 2, 3, 4... sin saltos
foreach ($vecinosRestantes as $index => $vecino) {
    DB::table('ruta_dia_cliente')
        ->where('id', $vecino->id)
        ->update(['orden' => $index + 1]);
}

    return response()->json(['message' => 'Visita eliminada de este día con éxito.']);
}
// 1. Asignar un cliente desde la bolsa de disponibles a un día/bloque de la ruta
public function asignarCliente(Request $request)
{
    $request->validate([
        'cliente_id' => 'required|exists:clientes,id',
        'ruta_id' => 'required|exists:rutas,id',
        'dia_destino' => 'required|integer|between:1,30', 
        'nuevo_orden' => 'required|integer',
    ]);

    $diaDestino = (int) $request->dia_destino;
    $ruta = DB::table('rutas')->find($request->ruta_id);

    if ($ruta && $ruta->tipo_ciclo === 'fin_de_semana') {
        if ($diaDestino === 1) $diaDestino = 6; 
        if ($diaDestino === 2) $diaDestino = 7; 
    }

    // El fkTienda se amarra estrictamente a la sesión activa para evitar cruces
    $fkTiendaUsuario = session('user_fkTienda');
    $centroCostos = $ruta ? $ruta->centro_costos : null;

    DB::table('ruta_dia_cliente')->updateOrInsert(
        [
            'ruta_id' => $request->ruta_id,
            'cliente_id' => $request->cliente_id,
            'dia_semana' => $diaDestino,
        ],
        [
            'fkTienda' => $fkTiendaUsuario, // Asegurado con la sesión contable
            'centro_costos' => $centroCostos,
            'orden' => $request->nuevo_orden,
            'updated_at' => now(),
        ]
    );

    if ($request->has('secuencia_completa')) {
        foreach ($request->secuencia_completa as $item) {
            DB::table('ruta_dia_cliente')
                ->where('ruta_id', $request->ruta_id)
                ->where('cliente_id', $item['cliente_id'])
                ->where('dia_semana', $diaDestino)
                ->update(['orden' => $item['orden']]);
        }
    }

    return response()->json(['message' => 'Cliente asignado con datos de sucursal']);
}

// 2. Mover un cliente de un día a otro, o cambiar su orden en la misma columna
public function moverCliente(Request $request)
{
    $request->validate([
        'cliente_id' => 'required|exists:clientes,id',
        'ruta_origen_id' => 'required|exists:rutas,id',
        'dia_origen' => 'required|integer|between:1,30',
        'ruta_destino_id' => 'required|exists:rutas,id',
        'dia_destino' => 'required|integer|between:1,30',
        'nuevo_orden' => 'required|integer',
    ]);

    $diaOrigen = (int) $request->dia_origen;
    $diaDestino = (int) $request->dia_destino;
    
    // Obtenemos los datos de la ruta destino por si se está moviendo a OTRA ruta diferente
    $rutaDestino = DB::table('rutas')->find($request->ruta_destino_id);
    $fkTiendaDestino = $rutaDestino ? $rutaDestino->fkTienda : null;
    $centroCostosDestino = $rutaDestino ? $rutaDestino->centro_costos : null;

    $rutaOrigen = DB::table('rutas')->find($request->ruta_origen_id);
    if ($rutaOrigen && $rutaOrigen->tipo_ciclo === 'fin_de_semana') {
        if ($diaOrigen === 1) $diaOrigen = 6;
        if ($diaOrigen === 2) $diaOrigen = 7;
    }
    
    if ($rutaDestino && $rutaDestino->tipo_ciclo === 'fin_de_semana') {
        if ($diaDestino === 1) $diaDestino = 6;
        if ($diaDestino === 2) $diaDestino = 7;
    }

    // Actualizar el registro inyectando los nuevos valores del destino contable
    DB::table('ruta_dia_cliente')
        ->where('cliente_id', $request->cliente_id)
        ->where('ruta_id', $request->ruta_origen_id)
        ->where('dia_semana', $diaOrigen)
        ->update([
            'ruta_id' => $request->ruta_destino_id,
            'dia_semana' => $diaDestino,
            'fkTienda' => $fkTiendaDestino,
            'centro_costos' => $centroCostosDestino,
            'orden' => $request->nuevo_orden,
            'updated_at' => now()
        ]);

    // Sincronizar el reordenamiento de vecinos
    if ($request->has('secuencia_completa')) {
        foreach ($request->secuencia_completa as $item) {
            DB::table('ruta_dia_cliente')
                ->where('ruta_id', $request->ruta_destino_id)
                ->where('cliente_id', $item['cliente_id'])
                ->where('dia_semana', $diaDestino)
                ->update(['orden' => $item['orden']]);
        }
    }

    return response()->json(['message' => 'Movimiento y secuencia de ruta guardados']);
}


// 4. Mover un DÍA COMPLETO de una ruta a otra ruta masivamente
public function moverDiaCompleto(Request $request)
{
    $request->validate([
        'ruta_origen_id' => 'required|exists:rutas,id',
        'dia_origen' => 'required|integer|between:1,30',
        'ruta_destino_id' => 'required|exists:rutas,id',
        'dia_destino' => 'required|integer|between:1,30',
    ]);

    // Obtenemos los datos de la ruta destino para actualizar las dependencias en bloque
    $rutaDestino = DB::table('rutas')->find($request->ruta_destino_id);
    $fkTiendaDestino = $rutaDestino ? $rutaDestino->fkTienda : null;
    $centroCostosDestino = $rutaDestino ? $rutaDestino->centro_costos : null;

    // Mueve todos los clientes y actualiza su Tienda y Centro de Costos en una sola consulta
    DB::table('ruta_dia_cliente')
        ->where('ruta_id', $request->ruta_origen_id)
        ->where('dia_semana', $request->dia_origen)
        ->update([
            'ruta_id' => $request->ruta_destino_id,
            'dia_semana' => $request->dia_destino,
            'fkTienda' => $fkTiendaDestino,
            'centro_costos' => $centroCostosDestino,
            'updated_at' => now()
        ]);

    return response()->json(['message' => 'Día completo transferido y reconfigurado contablemente']);
}


    
}
