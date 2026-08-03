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
    $visitas = DB::table('despachos_diarios_pilotos')
        ->join('clientes', 'despachos_diarios_pilotos.cliente_id', '=', 'clientes.id')
        ->join('personas', 'clientes.persona_id', '=', 'personas.id')
        ->join('rutas', 'despachos_diarios_pilotos.ruta_id', '=', 'rutas.id')
        ->select(
            'despachos_diarios_pilotos.*',
            'personas.razon_social as cliente_nombre',
            'personas.direccion as cliente_direccion',
            'personas.numero_documento as cliente_nit',
            'rutas.nombre as nombre_ruta'
        )
        ->where('despachos_diarios_pilotos.fecha_despacho', $hoy)
        ->where('despachos_diarios_pilotos.centro_costos', $centroCostosPiloto)
        ->where('despachos_diarios_pilotos.fkTienda', $fkTiendaUsuario)
        ->orderBy('despachos_diarios_pilotos.orden_visita', 'asc') // Respeta estrictamente la secuencia Kanban
        ->get();

    // 4. Retornar a la vista corregida (despacho en plural) con su compact completo
    return view('pilotos.depacho', compact('visitas', 'hoy', 'centroCostosPiloto', 'tecnico'));
}

    /**
     * Actualizar el estatus de la entrega desde el teléfono o la computadora del piloto
     */
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
