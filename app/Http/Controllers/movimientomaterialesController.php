<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller; // Asegúrate de que esta línea esté presente

use App\Models\Materialmanoobra;
use Illuminate\Http\Request;
use App\Http\Requests\StorePersonaRequest;
use App\Http\Requests\UpdateClienteRequest;
use App\Http\Requests\StoreClienteExistenteRequest;
use App\Models\Cliente;
use App\Models\User;
use App\Models\Documento;
use App\Models\MovimientoMateriales;
use App\Models\Persona;
use App\Models\Producto;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


class movimientomaterialesController extends Controller
{
    public function __construct()
    {
        // Aplicar middleware de permisos
        $this->middleware('permission:ver-movimientomateriales', ['only' => ['index']]);
        $this->middleware('permission:crear-movimientomateriales', ['only' => ['create', 'store', 'exist']]);
        $this->middleware('permission:editar-movimientomateriales', ['only' => ['edit', 'update']]);
        $this->middleware('permission:eliminar-movimientomateriales', ['only' => ['destroy']]);

    }

    public function index()
    {

                    if(!Auth::check()){
            return redirect()->route('login');
        }

        $fkTienda = session('user_fkTienda');
        $Estatus = session('user_estatus');

                if ($Estatus == 'ER') {

                    $materialmanoobra = Materialmanoobra::all();

                } else {
                    $materialmanoobra = Materialmanoobra::where('fkTienda',$fkTienda)->get();
                }



        return view('materialmanoobra.index', compact('materialmanoobra'));
    }
    public function reporteTransito()
{
    $fkTienda = session('user_fkTienda');

    // Buscamos movimientos 641 que no han sido "cerrados" por un 101
    $transito = DB::table('movimiento_materiales as m641')
        ->leftJoin('movimiento_materiales as m101', function($join) {
            $join->on('m101.referencia', '=', 'm641.documento_material')
                 ->where('m101.clase_movimiento', '=', '101');
        })
        ->select(
            'm641.documento_material as guia',
            'm641.fkMateriales',
            'm641.cantidad as cantidad_enviada',
            DB::raw('COALESCE(SUM(m101.cantidad), 0) as cantidad_recibida'),
            DB::raw('(m641.cantidad - COALESCE(SUM(m101.cantidad), 0)) as pendiente')
        )
        ->where('m641.clase_movimiento', '641')
        ->where('m641.fkTienda', $fkTienda)
        ->groupBy('m641.id', 'm641.documento_material', 'm641.fkMateriales', 'm641.cantidad')
        ->having('pendiente', '>', 0) // Solo lo que sigue "volando"
        ->get();

    return view('reportes.transito', compact('transito'));
}

function traslados(){
    MovimientoMateriales::create([
    'fkTienda' => $fkTienda,
    'fkMateriales' => $productoId,
    'fkLotes' => $loteId,
    'clase_movimiento' => '311',
    'almacen' => 'Bodega Central',
    'almacen_destino' => 'Tienda Norte', // Obligatorio por el Observer
    'cantidad' => 10,
    'documento_material' => 'TR-' . time(),
    'fecha_contabilizacion' => now(),
]);

}

    public function show($id)
    {
                        if(!Auth::check()){
            return redirect()->route('login');
        }

        // Lógica para mostrar un cliente específico
        $cliente = Cliente::find($id);
        return redirect()->route('clientes.index')->with('success', 'Cliente registrado');
    }
    public function create()
    {
                        if(!Auth::check()){
            return redirect()->route('login');
        }

        $materialmanoobra = Materialmanoobra::all();
        return view('materialmanoobra.create', compact('materialmanoobra'));
    }

    public function storeTraslado(Request $request)
{
    // fkTiendaActual es la que envía, fkTiendaDestino es la que recibe
    $request->validate([
        'producto_id' => 'required',
        'cantidad' => 'required|numeric|min:1',
        'fkTiendaDestino' => 'required'
    ]);

    try {
        DB::beginTransaction();

        $tiendaOrigenId = Auth::user()->fkTienda; // Tienda del usuario logueado
        $productoOrigen = Producto::where('id', $request->producto_id)
                                  ->where('fkTienda', $tiendaOrigenId)
                                  ->firstOrFail();

        if ($productoOrigen->stock < $request->cantidad) {
            return back()->withErrors(['error' => 'Stock insuficiente en origen.']);
        }

        // 1. Descontar Stock Origen
        $productoOrigen->decrement('stock', $request->cantidad);

        // 2. Aumentar o Crear Stock en Destino
        // Buscamos si el producto ya existe en la tienda destino por código
        $productoDestino = Producto::where('codigo', $productoOrigen->codigo)
                                   ->where('fkTienda', $request->fkTiendaDestino)
                                   ->first();

        if ($productoDestino) {
            $productoDestino->increment('stock', $request->cantidad);
        } else {
            // Si no existe, lo clonamos a la nueva tienda
            $productoDestino = $productoOrigen->replicate();
            $productoDestino->fkTienda = $request->fkTiendaDestino;
            $productoDestino->stock = $request->cantidad;
            $productoDestino->save();
        }

        // 3. Registrar en Movimiento_Materiales
        DB::table('movimiento_materiales')->insert([
            'fkTienda' => $tiendaOrigenId,
            'fkMateriales' => $productoOrigen->id,
            'clase_movimiento' => '301', // Código estándar para traslados
            'tipo_movimiento' => 'TRASLADO',
            'origen_uso' => 'traslado_entre_bodegas',
            'cantidad' => $request->cantidad,
            'fecha_contabilizacion' => now(),
            'created_at' => now(),
            'updated_at' => now(),
            // Agrega aquí los campos de 'centro' o 'almacen' según tus modelos de Centros
        ]);

        DB::commit();
        return redirect()->back()->with('success', 'Movimiento realizado con éxito.');

    } catch (\Exception $e) {
        DB::rollBack();
        return redirect()->back()->withErrors(['error' => 'Error: ' . $e->getMessage()]);
    }
}


    public function store(StorePersonaRequest $request)
    {
        try {
            DB::beginTransaction();
            $persona = Persona::create($request->validated());
            $persona->cliente()->create([
                'persona_id' => $persona->id
            ]);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al registrar cliente: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al registrar el cliente.');
        }

        return redirect()->route('clientes.index')->with('success', 'Cliente registrado');
    }

    public function exist(StoreClienteExistenteRequest $request)
    {
        try {
            DB::beginTransaction();

            // Buscar la persona existente
            $persona = Persona::findOrFail($request->persona_id);

            // Verificar si ya existe en clientes
            if ($persona->cliente) {
                return redirect()->back()->with('error', 'La persona ya está registrada como cliente.');
            }

            // Registrar como cliente
            $persona->cliente()->create(['persona_id' => $persona->id]);
            DB::commit();

            return redirect()->route('clientes.index')->with('success', 'Cliente registrado exitosamente.');
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al registrar cliente existente - Persona ID: ' . $request->persona_id . ' - Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al registrar el cliente.');
        }
    }

    public function edit(Cliente $cliente)
    {
                        if(!Auth::check()){
            return redirect()->route('login');
        }

        $cliente->load('persona.documento');
        $documentos = Documento::all();
        return view('cliente.edit', compact('cliente', 'documentos'));
    }

    public function update(UpdateClienteRequest $request, Cliente $cliente)
    {
        try {
            DB::beginTransaction();
            Persona::where('id', $cliente->persona->id)
                ->update($request->validated());
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar cliente: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al actualizar el cliente.');
        }

        return redirect()->route('clientes.index')->with('success', 'Cliente editado');
    }

    public function obtenerClientes()
    {
        $clientes = Cliente::select('id', 'persona_id')
        ->get();
        return response()->json($clientes);
    }

    public function listaClientes(Request $request)
    {
        $query = Cliente::with('persona')->orderBy('persona.nombre', 'asc');

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->whereHas('persona', function ($q) use ($search) {
                $q->where('nombre', 'like', "%$search%");
            });
        }

        $clientes = $query->paginate(10); // Paginar los resultados
        return response()->json($clientes);
    }

    public function destroy(string $id)
    {
        try {
                            if(!Auth::check()){
            return redirect()->route('login');
        }

            $persona = Persona::findOrFail($id);
            $nuevoEstado = $persona->estado == 1 ? 0 : 1;
            $mensaje = $nuevoEstado == 0 ? 'Cliente desactivado' : 'Cliente reactivado';

            $persona->update(['estado' => $nuevoEstado]);

            return redirect()->route('clientes.index')->with('success', $mensaje);
        } catch (Exception $e) {
            Log::error('Error al cambiar estado del cliente - ID: ' . $id . ' - Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al cambiar el estado del cliente.');
        }
    }
}
