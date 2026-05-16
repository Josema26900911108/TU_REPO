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

public function create()
{
    // Obtenemos los productos de la tienda para llenar el select de la vista
    $productos = Producto::where('fkTienda', auth()->user()->fkTienda)
                         ->where('estado', 1)
                         ->orderBy('nombre', 'asc')
                         ->get();

    return view('reglas.create', compact('productos'));
}

public function store(Request $request)
{
    // Validamos los datos
    $validated = $request->validate([
        'nombre'            => 'required|string|max:255',
        'tipo_regla'        => 'required|in:escala_cantidad,bonificacion,combo_mixto,descuento_fijo',
        'cantidad_minima'   => 'required|numeric|min:0',
        'cantidad_paso'     => 'nullable|numeric|min:0',
        'tipo_beneficio'    => 'required|in:precio_fijo,porcentaje,unidad_gratis',
        'valor_beneficio'   => 'required|numeric|min:0',
        'fecha_inicio'      => 'required|date',
        'fecha_fin'         => 'required|date|after_or_equal:fecha_inicio',
        'productos'         => 'nullable|array', // Los productos seleccionados
    ]);

    try {
        DB::beginTransaction();

        // 1. Crear la Regla
        $regla = new ReglaPrecio();
        $regla->fill($request->all());
        $regla->fkTienda = auth()->user()->fkTienda;
        $regla->prioritaria = $request->has('prioritaria') ? 1 : 0;
        $regla->save();

        // 2. Vincular con los productos seleccionados
        if ($request->has('productos')) {
            foreach ($request->productos as $producto_id) {
                ReglaPrecioAplicacion::create([
                    'regla_id'    => $regla->id,
                    'producto_id' => $producto_id,
                    'fkTienda'    => auth()->user()->fkTienda
                ]);
            }
        }

        DB::commit();
        return redirect()->route('reglas.index')->with('success', 'Regla creada con éxito.');

    } catch (\Exception $e) {
        DB::rollBack();
        return back()->withErrors('Error al guardar: ' . $e->getMessage())->withInput();
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
