<?php

namespace App\Http\Controllers;

use App\Models\ReglaPrecio;
use App\Models\Producto;
use App\Models\ReglaPrecioAplicacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\FacadesDB;


class ReglaPrecioController extends Controller
{


public function index()
{
    if (!auth()->check()) {
        return redirect()->route('login');
    }

               $fkTienda = session('user_fkTienda');


    if (is_null($fkTienda)) {
        $fkTienda = auth()->user()->idTienda ?? auth()->user()->tienda_id ?? 1; 
    }

    // Consultamos las reglas de la tienda e incluimos sus productos vinculados
    $reglas = ReglaPrecio::with('productos')
                ->where('fkTienda', $fkTienda)
                ->orderBy('created_at', 'desc')
                ->get();

    return view('reglas.index', compact('reglas'));
}


public function create()
{
            $fkTienda = session('user_fkTienda');

    // Obtenemos los productos de la tienda para llenar el select de la vista
    $productos = Producto::where('fkTienda', $fkTienda)
                         ->where('estado', 1)
                         ->orderBy('nombre', 'asc')
                         ->get();

    return view('reglas.create', compact('productos'));
}
public function show($id)
{
    $regla = ReglaPrecio::with('productos')->findOrFail($id);
    return view('reglas.show', compact('regla'));
}
public function store(Request $request)
{
    if (!auth()->check()) {
        return redirect()->back()->withErrors(['error' => 'Debes iniciar sesión para realizar esta acción.']);
    }

    $fkTienda = auth()->user()->fkTienda;

    if (is_null($fkTienda)) {
        $fkTienda = auth()->user()->idTienda ?? auth()->user()->tienda_id ?? 1; 
    }

    $request->validate([
        'nombre' => 'required|string|max:255',
        'tipo_regla' => 'required|in:escala_cantidad,bonificacion,combo_mixto,descuento_fijo',
        'cantidad_minima' => 'required|integer|min:1',
        'cantidad_paso' => 'nullable|integer|min:1',
        'tipo_beneficio' => 'required|in:precio_fijo,porcentaje,unidad_gratis',
        'valor_beneficio' => 'required|numeric|min:0',
        'fecha_inicio' => 'nullable|date',
        'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio',
        'prioritaria' => 'required|boolean',
        'requiere_confirmacion' => 'required|boolean',
        'productos' => 'required|array',
        'productos.*' => 'exists:productos,id'
    ]);

    // Usamos una transacción para asegurar que se guarde la regla Y sus productos asociados sin fallos
    DB::beginTransaction();

    try {
        // 1. Crear la Regla de Precio
        $reglaId = DB::table('reglas_precios')->insertGetId([
            'nombre' => $request->nombre,
            'tipo_regla' => $request->tipo_regla,
            'cantidad_minima' => $request->cantidad_minima,
            'cantidad_paso' => $request->cantidad_paso,
            'tipo_beneficio' => $request->tipo_beneficio,
            'valor_beneficio' => $request->valor_beneficio,
            'fecha_inicio' => $request->fecha_inicio,
            'fecha_fin' => $request->fecha_fin,
            'prioritaria' => $request->prioritaria,
            'requiere_confirmacion' => $request->requiere_confirmacion,
            'fkTienda' => $fkTienda,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // 2. Insertar de forma directa en tu tabla pivote real 'producto_regla_precio'
        foreach ($request->productos as $productoId) {
            DB::table('producto_regla_precio')->insert([
                'producto_id'     => $productoId,
                'regla_precio_id' => $reglaId
                // Nota: Tu tabla solo pide estos 3 campos (el ID es AutoIncremental), no lleva timestamps ni fkTienda
            ]);
        }

        DB::commit();
        return redirect()->route('reglas.index')->with('success', 'Regla de precio creada y asociada correctamente en producto_regla_precio.');

    } catch (\Exception $e) {
        DB::rollBack();
        return redirect()->back()->withErrors(['error' => 'Error en la base de datos: ' . $e->getMessage()]);
    }
}

    /**
     * Asignar una regla existente a un producto específico.
     */
    public function asignarAProducto(Request $request)
    {
        $producto = Producto::findOrFail($request->producto_id);
        $producto->reglasPrecios()->syncWithoutDetaching($request->regla_id);
        
        return response()->json(['message' => 'Regla vinculada al producto']);
    }

    public function destroy($id)
    {
        ReglaPrecio::destroy($id);
        return response()->json(['message' => 'Regla eliminada']);
    }
}
